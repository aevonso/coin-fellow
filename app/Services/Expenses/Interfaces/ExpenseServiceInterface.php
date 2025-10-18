<?php

namespace App\Services\Expenses\Interfaces;

use App\Models\Expense;
use App\Models\User;
use App\Services\Expenses\DTO\CreateExpensesDTO;
use App\Services\Expenses\DTO\UpdateExpenseDTO;
use Illuminate\Paginator\LengthAwarePaginator;

interface ExpenseServiceInterface {
    public function getGroupExpenses(User $user, string $groupId): LengthAwarePaginator;
    public function createExpense(User $user, CreateExpenseDTO $dto): Expense;
    public function updateExpense(User $user, string $expenseId, UpdateExpenseDTO $dto): Expense;
    public function deleteExpense(User $user, string $expenseId): void;
    public function getUserExpenses(User $user): LengthAwarePaginator;
}