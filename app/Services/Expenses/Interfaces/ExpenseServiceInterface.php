<?php

namespace App\Services\Expenses\Interfaces;

use App\Models\Expense;
use App\Models\User;
use App\Services\Expenses\DTO\CreateExpenseDTO; 
use App\Services\Expenses\DTO\UpdateExpenseDTO; 
use Illuminate\Pagination\LengthAwarePaginator;

interface ExpenseServiceInterface
{
    public function getGroupExpenses(User $user, string $groupId): LengthAwarePaginator;
    public function createExpense(User $user, CreateExpenseDTO $dto): Expense; 
    public function getExpense(User $user, string $expenseId): Expense;
    public function updateExpense(User $user, string $expenseId, UpdateExpenseDTO $dto): Expense; 
    public function deleteExpense(User $user, string $expenseId): void;
    public function getUserExpenses(User $user): LengthAwarePaginator;
}