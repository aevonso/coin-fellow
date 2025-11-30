<?php

namespace App\Services\Budgets\Interfaces;

use App\Models\Budget;
use App\Models\User;
use App\Services\Budgets\DTO\CreateBudgetDTO;
use App\Services\Budgets\DTO\UpdateBudgetDTO;
use App\Services\Budgets\DTO\BudgetStatsDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface BudgetServiceInterface
{
    public function getGroupBudgets(User $user, string $groupId): LengthAwarePaginator;
    public function createBudget(User $user, CreateBudgetDTO $dto): Budget;
    public function getBudget(User $user, string $budgetId): Budget;
    public function updateBudget(User $user, string $budgetId, UpdateBudgetDTO $dto): Budget;
    public function deleteBudget(User $user, string $budgetId): void;
    
    public function getBudgetStats(User $user, string $budgetId): BudgetStatsDTO;
    public function getGroupBudgetOverview(User $user, string $groupId): array;
    public function getUserBudgets(User $user): LengthAwarePaginator;
    
    public function checkBudgetAlerts(): void;
    public function getBudgetHistory(User $user, string $budgetId): Collection;
    
    public function getBudgetRecommendations(User $user, string $groupId): array;
}