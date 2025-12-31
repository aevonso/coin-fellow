<?php

namespace App\Services\Analytics\Interfaces;

use App\Models\User;
use App\Services\Analytics\DTO\AnalyticsFilterDTO;
use App\Services\Analytics\DTO\ChartDataDTO;
use Illuminate\Support\Collection;

interface AnalyticsServiceInterface 
{
    public function getGroupSpendingTrend(string $groupId, AnalyticsFilterDTO $filters): ChartDataDTO;
    public function getCategoryBreakdown(string $groupId, AnalyticsFilterDTO $filters): ChartDataDTO;
    public function getUserSpendingComparison(string $groupId, AnalyticsFilterDTO $filters): ChartDataDTO;
    public function getExpenseDistribution(string $groupId, AnalyticsFilterDTO $filters): ChartDataDTO;

    public function getTopSpendingCategories(string $groupId, AnalyticsFilterDTO $filters): Collection;
    public function getUserSpendingStats(string $groupId, AnalyticsFilterDTO $filters): Collection;
    public function getPeriodComparison(string $groupId, AnalyticsFilterDTO $filters): array;

    public function getSavingsOpportunities(string $groupId, AnalyticsFilterDTO $filters): array;
    public function getSpendingPredictions(string $groupId, AnalyticsFilterDTO $filters): array;

    public function getGroupAnalyticsDashboard(string $groupId, AnalyticsFilterDTO $filters): array;
    public function getUserAnalyticsDashboard(User $user, AnalyticsFilterDTO $filters): array;

    public function generateAnalyticsReport(string $groupId, AnalyticsFilterDTO $filters): string;

}