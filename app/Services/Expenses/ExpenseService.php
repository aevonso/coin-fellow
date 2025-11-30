<?php

namespace App\Services\Expenses;

use App\Models\Expense;
use App\Models\User;
use App\Models\Group;
use App\Services\Balances\Interfaces\BalanceServiceInterface;
use App\Services\Notifications\Interfaces\NotificationServiceInterface;
use App\Services\Expenses\DTO\CreateExpenseDTO;
use App\Services\Expenses\DTO\UpdateExpenseDTO;
use App\Services\Expenses\Interfaces\ExpenseServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseService implements ExpenseServiceInterface {

    public function __construct (
        private BalanceServiceInterface $balanceService,
        private NotificationServiceInterface $notificationService
    ) {}

    public function getGroupExpenses(User $user, string $groupId): LengthAwarePaginator {
        $group = Group::findOrFail($groupId); 

        if(!$group->users->contains($user->id)) {
            throw ValidationException::withMessages([
                'group' => ['Вы не являетесь участником этой группы'],
            ]);
        }

        return Expense::where('group_id', $groupId)
            ->with(['payer', 'category', 'participants'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function createExpense(User $user, CreateExpenseDTO $dto): Expense {
        $group = Group::with('users')->findOrFail($dto->groupId);

        if(!$group->users->contains($user->id)) {
            throw ValidationException::withMessages([
                'group' => ['Вы не являетесь участником этой группы'],
            ]);
        }

        return DB::transaction(function() use ($user, $dto, $group) {
            $expense = Expense::create([
                'group_id' => $dto->groupId,
                'payer_id' => $user->id,
                'category_id' => $dto->categoryId,
                'description' => $dto->description,
                'amount' => $dto->amount,
                'date' => $dto->date,
            ]);

            $this->handleParticipants($expense, $dto->participants, $group);
            
            $this->balanceService->calculateBalancesForGroup($dto->groupId);

            $this->notifyExpenseCreated($expense, $user, $group);

            return $expense->load(['payer', 'category' , 'participants']);
        });  
    }
    
    private function handleParticipants(Expense $expense, ?array $participants, Group $group): void
    {
        if ($participants === null) {
            $participants = $group->users->pluck('id')->toArray();
        }

        if (empty($participants)) {
            throw ValidationException::withMessages([
                'participants' => ['Участников для оплаты этого счета не найдено'],
            ]);
        }

        $expense->participants()->detach();

        $share = $expense->amount / count($participants);
        
        foreach ($participants as $participantId) {
            $expense->participants()->attach($participantId, [
                'share' => $share
            ]);
        }
    }

    public function getExpense(User $user, string $expenseId): Expense
    {
        $expense = Expense::with(['payer', 'category', 'participants', 'group.users'])
            ->findOrFail($expenseId);

        if(!$expense->group->users->contains($user->id)) {
            throw ValidationException::withMessages([
                'group' => ['Вы не являетесь участником этой группы'],
            ]);
        }

        return $expense;
    }

    public function updateExpense(User $user, string $expenseId, UpdateExpenseDTO $dto): Expense
    {
        $expense = Expense::with(['group.users'])->findOrFail($expenseId);

        $this->checkExpensePermissions($user, $expense);

        return DB::transaction(function () use ($expense, $dto, $user) {
            $oldAmount = $expense->amount;
            
            if ($dto->description) {
                $expense->description = $dto->description;
            }
            if ($dto->amount) {
                $expense->amount = $dto->amount;
            }
            if ($dto->date) {
                $expense->date = $dto->date;
            }
            if ($dto->categoryId !== null) {
                $expense->category_id = $dto->categoryId;
            }

            $expense->save();

            if ($dto->participants !== null) {
                $this->handleParticipants($expense, $dto->participants, $expense->group);
            }

            $this->balanceService->calculateBalancesForGroup($expense->group_id);

            if ($dto->amount && $dto->amount != $oldAmount) {
                $this->notifyExpenseUpdated($expense, $user);
            }

            return $expense->load(['payer', 'category', 'participants']);
        });
    }

    public function deleteExpense(User $user, string $expenseId): void
    {
        $expense = Expense::with(['group.users'])->findOrFail($expenseId);
        $groupId = $expense->group_id;

        $this->checkExpensePermissions($user, $expense);

        DB::transaction(function () use ($expense, $groupId, $user) {
            $this->notifyExpenseDeleted($expense, $user);
            
            $expense->delete();

            $this->balanceService->calculateBalancesForGroup($groupId);
        });
    }

    public function getUserExpenses(User $user): LengthAwarePaginator
    {
        return Expense::where('payer_id', $user->id)
            ->orWhereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['group', 'category', 'payer'])
            ->orderBy('date', 'desc')
            ->paginate(20);
    }

    private function checkExpensePermissions(User $user, Expense $expense): void
    {
        $isPayer = $expense->payer_id === $user->id;
        $isGroupAdmin = $expense->group->isUserAdmin($user);

        if (!$isPayer && !$isGroupAdmin) {
            throw ValidationException::withMessages([
                'permission' => ['У вас нет разрешения на изменение этого расхода'],
            ]);
        }
    }

    private function notifyExpenseCreated(Expense $expense, User $creator, Group $group): void
    {
        $participants = $group->users->where('id', '!=', $creator->id);
        
        foreach ($participants as $participant) {
            $this->notificationService->notifyNewExpense($participant, [
                'expense_id' => $expense->id,
                'description' => $expense->description,
                'amount' => $expense->amount,
                'payer_name' => $creator->first_name ?? $creator->username,
                'group_id' => $expense->group_id,
                'group_name' => $group->name,
                'category_name' => $expense->category?->name,
                'date' => $expense->date->format('Y-m-d'),
            ]);
        }
    }

    private function notifyExpenseUpdated(Expense $expense, User $editor): void
    {
        $participants = $expense->group->users->where('id', '!=', $editor->id);
        
        foreach ($participants as $participant) {
            $this->notificationService->createNotification(
                new \App\Services\Notifications\DTO\CreateNotificationDTO(
                    userId: $participant->id,
                    type: \App\Models\Notification::TYPE_NEW_EXPENSE,
                    message: "Расход обновлен: {$expense->description} - {$expense->amount}₽",
                    groupId: $expense->group_id,
                    data: [
                        'expense_id' => $expense->id,
                        'description' => $expense->description,
                        'amount' => $expense->amount,
                        'editor_name' => $editor->first_name ?? $editor->username,
                        'group_id' => $expense->group_id,
                        'action' => 'updated',
                    ]
                )
            );
        }
    }

    private function notifyExpenseDeleted(Expense $expense, User $deleter): void
    {
        $participants = $expense->group->users->where('id', '!=', $deleter->id);
        
        foreach ($participants as $participant) {
            $this->notificationService->createNotification(
                new \App\Services\Notifications\DTO\CreateNotificationDTO(
                    userId: $participant->id,
                    type: \App\Models\Notification::TYPE_NEW_EXPENSE,
                    message: "Расход удален: {$expense->description} - {$expense->amount}₽",
                    groupId: $expense->group_id,
                    data: [
                        'expense_id' => $expense->id,
                        'description' => $expense->description,
                        'amount' => $expense->amount,
                        'deleter_name' => $deleter->first_name ?? $deleter->username,
                        'group_id' => $expense->group_id,
                        'action' => 'deleted',
                    ]
                )
            );
        }
    }
}