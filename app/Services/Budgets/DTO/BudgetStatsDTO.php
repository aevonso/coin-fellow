<?php

namespace App\Services\Budgets\DTO;

use Spatie\LaravelData\Data;

class BudgetStatsDTO extends Data
{
    public function __construct(
        public float $spentAmount,
        public float $remainingAmount,
        public float $usagePercentage,
        public bool $isExceeded,
        public int $expensesCount,
        public float $averageExpense,
        public array $dailySpending = [],
        public array $categoryBreakdown = []
    ) {}
}