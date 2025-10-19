<?php

namespace App\Services\Expenses;

use App\Models\Expense;
use App\Models\User;
use App\Models\Group;
use App\Services\Expenses\DTO\CreateExpenseDTO;
use App\Services\Expenses\DTO\UpdateExpenseDTO;
use App\Services\Expenses\Interfaces\ExpenseServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validator\ValidatorException;

class ExpenseService implements ExpenseServiceInterface {
    public function getGroupExpenses(User $user, string $groupId): LengthAwarePaginator {
        $group = Group::findOrFail($groupId); 

        if(!$group->users->contains($user->id)) {
            throw ValidatorException::withMessages([
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
        $group = Group::findOrFail($dto->groupId);

         if(!$group->users->contains($user->id)) {
            throw ValidatorException::withMessages([
                'group' => ['Вы не являетесь участником этой группы'],
            ]);
        }

        $expense = Expense::create([
            'group_id' => $dto->groupId,
            'payer_id' => $user->id,
            'category_id' => $dto->categoryId,
            'description' => $dto->description,
            'amount' => $dto->amount,
            'date' => $dto->date,
        ]);

        $this->handleParticipants($expense, $dto->participants);
        
        return $expense->load(['payer', 'category' , 'participants']);
    }
    
    private function handleParticipants(Expense $expense, ?array $participants): void{
     
        if (!$expense->relationLoaded('group')) {
        $expense->load('group.users');
    }

    if ($participants === null) {
        $participants = $expense->group->users->pluck('id')->toArray();
    }

    if (empty($participants)) {
        throw ValidationException::withMessages([
            'participants' => ['No participants found for this expense'],
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
            throw ValidatorException::withMessages([
                'group' => ['Вы не являетесь участником этой группы'],
            ]);
        }

        return $expense;
    }

    public function updateExpense(User $user, string $expenseId, UpdateExpenseDTO $dto): Expense
    {
        $expense = Expense::with(['group'])->findOrFail($expenseId);

        $this->checkExpensePermissions($user, $expense);

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
            $this->handleParticipants($expense, $dto->participants);
        }

        return $expense->load(['payer', 'category', 'participants']);
    }

    public function deleteExpense(User $user, string $expenseId): void
    {
        $expense = Expense::with(['group'])->findOrFail($expenseId);

        $this->checkExpensePermissions($user, $expense);

        $expense->delete();
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



    private function splitEqually(Expense $expense, array $participants): void
    {
        $share = $expense->amount / count($participants);
        
        foreach ($participants as $participantId) {
            $expense->participants()->attach($participantId, [
                'share' => $share
            ]);
        }
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
}