<?php

namespace App\Services\RecurringExpenses\Interfaces;

use App\Models\RecurringExpense;
use App\Models\User;
use App\Services\RecurringExpenses\DTO\CreateRecurringExpenseDTO;
use App\Services\RecurringExpenses\DTO\UpdateRecurringExpenseDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RecurringExpenseServiceInterface
{
    public function getGroupRecurringExpenses(User $user, string $groupId): LengthAwarePaginator;
    public function createRecurringExpense(User $user, CreateRecurringExpenseDTO $dto): RecurringExpense;
    public function getRecurringExpense(User $user, string $recurringExpenseId): RecurringExpense;
    public function updateRecurringExpense(User $user, string $recurringExpenseId, UpdateRecurringExpenseDTO $dto): RecurringExpense;
    public function deleteRecurringExpense(User $user, string $recurringExpenseId): void;
    public function toggleRecurringExpense(User $user, string $recurringExpenseId, bool $isActive): RecurringExpense;
    
    public function getUserRecurringExpenses(User $user): LengthAwarePaginator;
    public function getUpcomingRecurringExpenses(User $user, string $groupId): Collection;
    public function getRecurringExpenseHistory(User $user, string $recurringExpenseId): Collection;
    
    public function generateDueRecurringExpenses(): int;
    public function previewNextOccurrences(string $recurringExpenseId, int $count = 5): array;
    
    public function skipNextOccurrence(User $user, string $recurringExpenseId): RecurringExpense;
    public function generateNextOccurrence(User $user, string $recurringExpenseId): void;
}