<?php

namespace App\Services\Balances;

use App\Models\Balance;
use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use App\Services\Balances\DTO\RecalculateBalancesDTO;
use App\Services\Balances\DTO\SettlementDTO;
use App\Services\Balances\Interfaces\BalanceServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class BalanceService implements BalanceServiceInterface 
{
    public function calculateBalancesForGroup(string $groupId): void {
        $group = Group::with(['expenses.participants', 'users'])->findOrFail($groupId);

        DB::transaction(function () use ($group){
            Balance::forGroup($group->id)->delete();

            foreach($group->expenses as $expense) {
                $this->calculateExpenseBalances($expense);
            }

            $this->optimizeBalances($group->id);
        });
    }

    private function calculateExpenseBalances(Expense $expense): void {
        $payerId = $expense->payer_id;
        $totalAmount = $expense->amount;
        $participants = $expense->participants;

        if($participants->isEmpty()) return;

        $sharePerParticipant = $totalAmount / $participants->count();

        foreach($participants as $participant) {
            if($participant->id !== $payerId) {
                $this->updateBalance(
                    $expense->group_id,
                    $participant->id,
                    $payerId,
                    $sharePerParticipant
                );
            }
        }
    }

    private function updateBalance(string $groupId, string $fromUserId, string $toUserId, float $amount): void {
        if($amount <= 0) {
            return;
        }

        $reverseBalance = Balance::forGroup($groupId)
            ->betweenUsers($toUserId, $fromUserId)
            ->first();

        if($reverseBalance && $reverseBalance->amount > 0) {
            if($reverseBalance->amount >= $amount) {
                $reverseBalance->amount -=$amount;
                $reverseBalance->save();

                if($reverseBalance->amount ==0) {
                    $reverseBalance->delete();
                }
                return;
            } else {
                $amount -=$reverseBalance->amount;
                $reverseBalance->delete();
            }
        }
        $balance = Balance::forGroup($groupId)
            ->betweenUsers($toUserId, $fromUserId)
            ->first();

        if($balance) {
            $balance->amount += $amount;
            $balance->save();
        } else {
            Balance::create([
                'group_id' => $groupId,
                'from_user_id' => $fromUserId,
                'to_user_id' => $to_user_id,
                'amount' => $amount
            ]);
        }
    }

    public function getGroupBalances(string $groupId): Collection 
    {
        return Balance::with(['fromUser', 'toUser'])
            ->forGroup($groupId)
            ->where('amount', '>', 0)
            ->get();
    }

    public function getUserBalancesInGroup(string $groupId, string $userId) : Collection 
    {
        return Balance::with(['fromUser', 'toUser'])
            ->forGroup($groupId)
            ->forUser($userId)
            ->where('amount', '>', 0)
            ->get();
    }

     public function getSimplifiedDebts(string $groupId): Collection
    {
        $balances = $this->getGroupBalances($groupId);
        return $balances->map(function ($balance) {
            return [
                'from_user' => $balance->fromUser,
                'to_user' => $balance->toUser,
                'amount' => (float) $balance->amount
            ];
        });
    }

     public function recalculateBalances(RecalculateBalancesDTO $dto): void
    {
        $this->calculateBalancesForGroup($dto->groupId);
    }

      public function settleDebt(SettlementDTO $dto): void
    {
        DB::transaction(function () use ($dto) {
            $balance = Balance::forGroup($dto->groupId)
                ->betweenUsers($dto->fromUserId, $dto->toUserId)
                ->firstOrFail();

            if ($balance->amount < $dto->amount) {
                throw ValidationException::withMessages([
                    'amount' => ['Сумма расчетов превышает сумму долга'],
                ]);
            }

            if ($balance->amount == $dto->amount) {
                $balance->delete();
            } else {
                $balance->amount -= $dto->amount;
                $balance->save();
            }
        });
    }

    public function getBalanceSummary(string $groupId, string $userId): array
    {
        $balances = $this->getUserBalancesInGroup($groupId, $userId);
        
        $totalOwed = 0;
        $totalOwedToYou = 0;

        foreach ($balances as $balance) {
            if ($balance->from_user_id === $userId) {
                $totalOwed += $balance->amount;
            } else {
                $totalOwedToYou += $balance->amount;
            }
        }

        return [
            'total_owed' => $totalOwed,
            'total_owed_to_you' => $totalOwedToYou,
            'net_balance' => $totalOwedToYou - $totalOwed,
        ];
    }

    private function optimizeBalances(string $groupId): void
    {
        Balance::forGroup($groupId)
            ->where('amount', '<=', 0)
            ->delete();
    }
}