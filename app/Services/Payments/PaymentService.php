<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\User;
use App\Models\Group;
use App\Services\Balances\Interfaces\BalanceServiceInterface;
use App\Services\Payments\DTO\CreatePaymentDTO;
use App\Services\Payments\DTO\UpdatePaymentDTO;
use App\Services\Payments\Interfaces\PaymentServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private BalanceServiceInterface $balanceService
    ) {}

    public function getGroupPayments(User $user, string $groupId): LengthAwarePaginator
    {
        $group = Group::findOrFail($groupId);

        if (!$group->users->contains($user->id)) {
            throw ValidationException::withMessages([
                'group' => ['Вы не являетесь участником этой группы'],
            ]);
        }

        return Payment::forGroup($groupId)
            ->with(['fromUser', 'toUser', 'group'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function createPayment(User $user, CreatePaymentDTO $dto): Payment
    {
        $group = Group::findOrFail($dto->groupId);

        if (!$group->users->contains($user->id)) {
            throw ValidationException::withMessages([
                'group' => ['Вы не являетесь участником этой группы'],
            ]);
        }

        
        if ($user->id !== $dto->fromUserId) {
            throw ValidationException::withMessages([
                'from_user_id' => ['Вы можете создавать платежи только от себя'],
            ]);
        }

        $debtAmount = $this->getDebtAmount($dto->groupId, $dto->fromUserId, $dto->toUserId);
        
        if ($debtAmount < $dto->amount) {
            throw ValidationException::withMessages([
                'amount' => ['Сумма платежа превышает сумму долга'],
            ]);
        }

        return DB::transaction(function () use ($dto) {
            $payment = Payment::create([
                'group_id' => $dto->groupId,
                'from_user_id' => $dto->fromUserId,
                'to_user_id' => $dto->toUserId,
                'amount' => $dto->amount,
                'date' => $dto->date,
                'notes' => $dto->notes,
                'status' => Payment::STATUS_PENDING,
            ]);

            return $payment->load(['fromUser', 'toUser', 'group']);
        });
    }

    public function getPayment(User $user, string $paymentId): Payment
    {
        $payment = Payment::with(['fromUser', 'toUser', 'group.users'])
            ->findOrFail($paymentId);

      
        if (!$payment->group->users->contains($user->id)) {
            throw ValidationException::withMessages([
                'payment' => ['Вы не являетесь участником этой платежной группы'],
            ]);
        }

        return $payment;
    }

    public function updatePayment(User $user, string $paymentId, UpdatePaymentDTO $dto): Payment
    {
        $payment = Payment::with(['group'])->findOrFail($paymentId);

        $this->checkPaymentPermissions($user, $payment);

        
        if (!$payment->isPending()) {
            throw ValidationException::withMessages([
                'payment' => ['Могут быть обновлены только отложенные платежи'],
            ]);
        }

        if ($dto->notes !== null) {
            $payment->notes = $dto->notes;
        }

        $payment->save();

        return $payment->load(['fromUser', 'toUser', 'group']);
    }

    public function deletePayment(User $user, string $paymentId): void
    {
        $payment = Payment::with(['group'])->findOrFail($paymentId);

        $this->checkPaymentPermissions($user, $payment);

       
        if (!$payment->isPending()) {
            throw ValidationException::withMessages([
                'payment' => ['Можно удалить только отложенные платежи'],
            ]);
        }

        $payment->delete();
    }

    public function confirmPayment(User $user, string $paymentId): Payment
    {
        $payment = Payment::with(['group'])->findOrFail($paymentId);

        
        if ($user->id !== $payment->to_user_id) {
            throw ValidationException::withMessages([
                'payment' => ['Подтвердить платеж может только получатель платежа'],
            ]);
        }

        if (!$payment->isPending()) {
            throw ValidationException::withMessages([
                'payment' => ['Могут быть подтверждены только отложенные платежи'],
            ]);
        }

        return DB::transaction(function () use ($payment) {
            $payment->status = Payment::STATUS_CONFIRMED;
            $payment->save();

        
            $this->settlePayment($payment);

            return $payment->load(['fromUser', 'toUser', 'group']);
        });
    }

    public function rejectPayment(User $user, string $paymentId): Payment
    {
        $payment = Payment::with(['group'])->findOrFail($paymentId);

        
        if ($user->id !== $payment->to_user_id) {
            throw ValidationException::withMessages([
                'payment' => ['Отклонить платеж может только получатель платежа'],
            ]);
        }

        if (!$payment->isPending()) {
            throw ValidationException::withMessages([
                'payment' => ['Только отложенные платежи могут быть отклонены'],
            ]);
        }

        $payment->status = Payment::STATUS_REJECTED;
        $payment->save();

        return $payment->load(['fromUser', 'toUser', 'group']);
    }

    public function getUserPayments(User $user): LengthAwarePaginator
    {
        return Payment::forUser($user->id)
            ->with(['fromUser', 'toUser', 'group'])
            ->orderBy('date', 'desc')
            ->paginate(20);
    }

    public function getPaymentStatistics(User $user, string $groupId): array
    {
        $group = Group::findOrFail($groupId);

        if (!$group->users->contains($user->id)) {
            throw ValidationException::withMessages([
                'group' => ['Вы не являетесь участником этой группы'],
            ]);
        }

        $payments = Payment::forGroup($groupId)
            ->selectRaw('status, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('status')
            ->get();

        $totalPayments = Payment::forGroup($groupId)->count();
        $totalAmount = Payment::forGroup($groupId)->sum('amount');
        $confirmedAmount = Payment::forGroup($groupId)->confirmed()->sum('amount');

        return [
            'total_payments' => $totalPayments,
            'total_amount' => (float) $totalAmount,
            'confirmed_amount' => (float) $confirmedAmount,
            'by_status' => $payments->mapWithKeys(function ($item) {
                return [$item->status => [
                    'count' => (int) $item->count,
                    'amount' => (float) $item->total_amount
                ]];
            })->toArray()
        ];
    }

    public function getPendingPayments(User $user, string $groupId): Collection
    {
        $group = Group::findOrFail($groupId);

        if (!$group->users->contains($user->id)) {
            throw ValidationException::withMessages([
                'group' => ['Вы не являетесь участником этой группы'],
            ]);
        }

        return Payment::forGroup($groupId)
            ->pending()
            ->with(['fromUser', 'toUser'])
            ->where('to_user_id', $user->id)
            ->get();
    }

    public function settlePayment(User $user, string $paymentId): void
    {
        $payment = Payment::findOrFail($paymentId);

        // Проверяем права
        if ($user->id !== $payment->to_user_id) {
            throw ValidationException::withMessages([
                'payment' => ['Только получатель платежа может произвести оплату'],
            ]);
        }

        if (!$payment->isConfirmed()) {
            throw ValidationException::withMessages([
                'payment' => ['Могут быть произведены только подтвержденные платежи'],
            ]);
        }


        $this->balanceService->calculateBalancesForGroup($payment->group_id);
    }

    private function getDebtAmount(string $groupId, string $fromUserId, string $toUserId): float
    {
        $balance = \App\Models\Balance::forGroup($groupId)
            ->betweenUsers($fromUserId, $toUserId)
            ->first();

        return $balance ? (float) $balance->amount : 0;
    }

    private function checkPaymentPermissions(User $user, Payment $payment): void
    {
        $isPayer = $payment->from_user_id === $user->id;
        $isRecipient = $payment->to_user_id === $user->id;
        $isGroupAdmin = $payment->group->isUserAdmin($user);

        if (!$isPayer && !$isRecipient && !$isGroupAdmin) {
            throw ValidationException::withMessages([
                'permission' => ['У вас нет разрешения на изменение этого платежа'],
            ]);
        }
    }
}