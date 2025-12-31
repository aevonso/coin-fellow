<?php

namespace App\Services\Analytics;

use App\Models\User;
use App\Models\Expense;
use App\Models\Group;
use App\Services\Analytics\DTO\AnalyticsFilterDTO;
use App\Services\Analytics\DTO\ChartDataDTO;
use App\Services\Analytics\DTO\ExpenseTrendDTO;
use App\Services\Analytics\DTO\UserSpendingDTO;
use App\Services\Analytics\Interfaces\AnalyticsServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnalyticsService implements AnalyticsServiceInterface 
{
    const CASHE_PREFIX = 'analytics:';
    const CASHE_TTL = 3600;

    public function getGroupSpendingTrend(string $groupId, AnalyticsFilterDTO $filters): ChartDataDTO 
    {
        $casheKey = self::CACHE_PREFIX . "spending_trend:{$groupId}:" .md5(serialize($filters->toArray()));

        return Cashe::remember($casheKey, self::CASHE_TTL, function() use ($groupId, $filters){
            $query = $this->buildBaseQuery($groupId, $filters);

            $trendData = $query->selectRaw($this->getPeriodSelect($filter->period). ' as period,
                                            SUM(amount) as total_amount,
                                            COUNT(*) as expenses_count')
                                ->groupBy('period')
                                ->orderBy('period')
                                ->get();
            $labels = $trendData->pluck('period')->toArray();
            $amounts = $trendData->pluck('total_amount')->map(fn($val) => (float) $val)->toArray();
            $counts = $trendData->pluck('expenses_count')->toArray();

            return new ChartDataDTO(
                labels: $labels,
                datasets: [
                    [
                        'label' => 'Сумма расходов',
                        'data' => $amounts,
                        'borderColor' => '#3498db',
                        'backgroundColor' => 'rgba(52,152,219,0.1)',
                        'yAxisID' => 'y'
                    ],
                    [
                        'label' => 'Количество расходов',
                        'data' => $counts,
                        'borderColor' => '#e74c3c',
                        'backgroundColor' => rgba(231,76,60,0.1),
                        'yAxisID' => 'y1'
                    ]
                ],
                metadata: [
                    'total_amount' => array_sum($amounts),
                    'total_count' => array_sum($counts),
                    'average_amount' => count($amounts) > 0 ? array_sum($amounts)/count($amounts) : 0
                ]
            );
        });
    }

    public function getCategoryBreakdown(string $groupId, AnalyticsFilterDTO $filters): ChartDataDTO
    {
        $cacheKey = self::CACHE_PREFIX . "category_breakdown:{$groupId}:" . md5(serialize($filters->toArray()));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($groupId, $filters) {
            $query = $this->buildBaseQuery($groupId, $filters);

            $categoryData = $query
                ->join('categories', 'expenses.category_id', '=', 'categories.id')
                ->selectRaw('categories.name as category_name,
                           categories.color as category_color,
                           SUM(expenses.amount) as total_amount,
                           COUNT(*) as expenses_count')
                ->groupBy('categories.id', 'categories.name', 'categories.color')
                ->orderByDesc('total_amount')
                ->limit($filters->limit ?? 10)
                ->get();

            $labels = $categoryData->pluck('category_name')->toArray();
            $amounts = $categoryData->pluck('total_amount')->map(fn($val) => (float) $val)->toArray();
            $colors = $categoryData->pluck('category_color')->toArray();
            $counts = $categoryData->pluck('expenses_count')->toArray();

            $totalAmount = array_sum($amounts);
            $percentages = array_map(fn($amount) => $totalAmount > 0 ? round(($amount / $totalAmount) * 100, 1) : 0, $amounts);

            return new ChartDataDTO(
                labels: $labels,
                datasets: [
                    [
                        'label' => 'Расходы по категориям',
                        'data' => $amounts,
                        'backgroundColor' => $colors,
                        'borderColor' => array_map(fn($color) => $this->darkenColor($color), $colors),
                        'borderWidth' => 2
                    ]
                ],
                metadata: [
                    'total_amount' => $totalAmount,
                    'percentages' => array_combine($labels, $percentages),
                    'expenses_count' => array_sum($counts)
                ]
            );
        });
    }

    public function getUserSpendingComparison(string $groupId, AnalyticsFilterDTO $filters): ChartDataDTO
    {
        $cacheKey = self::CACHE_PREFIX . "user_comparison:{$groupId}:" . md5(serialize($filters->toArray()));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($groupId, $filters) {
            $userData = DB::table('expenses')
                ->join('users', 'expenses.payer_id', '=', 'users.id')
                ->where('expenses.group_id', $groupId)
                ->when($filters->startDate, function ($query, $startDate) {
                    $query->where('expenses.date', '>=', $startDate);
                })
                ->when($filters->endDate, function ($query, $endDate) {
                    $query->where('expenses.date', '<=', $endDate);
                })
                ->when($filters->categoryIds, function ($query, $categoryIds) {
                    $query->whereIn('expenses.category_id', $categoryIds);
                })
                ->selectRaw('users.id as user_id,
                           CONCAT(users.first_name, " ", COALESCE(users.last_name, "")) as user_name,
                           SUM(expenses.amount) as total_spent,
                           COUNT(*) as expenses_count')
                ->groupBy('users.id', 'users.first_name', 'users.last_name')
                ->orderByDesc('total_spent')
                ->limit($filters->limit ?? 10)
                ->get();

            $labels = $userData->pluck('user_name')->toArray();
            $amounts = $userData->pluck('total_spent')->map(fn($val) => (float) $val)->toArray();
            $counts = $userData->pluck('expenses_count')->toArray();

            $averageAmount = count($amounts) > 0 ? array_sum($amounts) / count($amounts) : 0;

            return new ChartDataDTO(
                labels: $labels,
                datasets: [
                    [
                        'label' => 'Общая сумма',
                        'data' => $amounts,
                        'backgroundColor' => '#3498db',
                        'borderColor' => '#2980b9'
                    ],
                    [
                        'label' => 'Количество расходов',
                        'data' => $counts,
                        'backgroundColor' => '#e74c3c',
                        'borderColor' => '#c0392b',
                        'type' => 'line',
                        'yAxisID' => 'y1'
                    ]
                ],
                metadata: [
                    'total_spent' => array_sum($amounts),
                    'average_per_user' => $averageAmount,
                    'top_spender' => $labels[0] ?? null,
                    'top_amount' => $amounts[0] ?? 0
                ]
            );
        });
    }

    public function getExpenseDistribution(string $groupId, AnalyticsFilterDTO $filters): ChartDataDTO
    {
        $cacheKey = self::CACHE_PREFIX . "expense_distribution:{$groupId}:" . md5(serialize($filters->toArray()));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($groupId, $filters) {
            $ranges = [
                ['min' => 0, 'max' => 500, 'label' => '0-500'],
                ['min' => 501, 'max' => 1000, 'label' => '501-1000'],
                ['min' => 1001, 'max' => 2000, 'label' => '1001-2000'],
                ['min' => 2001, 'max' => 5000, 'label' => '2001-5000'],
                ['min' => 5001, 'max' => null, 'label' => '5000+']
            ];

            $distributionData = collect($ranges)->map(function ($range) use ($groupId, $filters) {
                $query = $this->buildBaseQuery($groupId, $filters);

                $query->where('amount', '>=', $range['min']);
                if ($range['max'] !== null) {
                    $query->where('amount', '<=', $range['max']);
                }

                return [
                    'range' => $range['label'],
                    'count' => $query->count(),
                    'total_amount' => (float) $query->sum('amount')
                ];
            });

            $labels = $distributionData->pluck('range')->toArray();
            $counts = $distributionData->pluck('count')->toArray();
            $amounts = $distributionData->pluck('total_amount')->toArray();

            return new ChartDataDTO(
                labels: $labels,
                datasets: [
                    [
                        'label' => 'Количество расходов',
                        'data' => $counts,
                        'backgroundColor' => '#9b59b6',
                        'borderColor' => '#8e44ad'
                    ],
                    [
                        'label' => 'Общая сумма',
                        'data' => $amounts,
                        'backgroundColor' => '#f1c40f',
                        'borderColor' => '#f39c12',
                        'type' => 'line'
                    ]
                ],
                metadata: [
                    'total_expenses' => array_sum($counts),
                    'total_amount' => array_sum($amounts),
                    'most_common_range' => $labels[array_search(max($counts), $counts)] ?? null
                ]
            );
        });
    }

    public function getTopSpendingCategories(string $groupId, AnalyticsFilterDTO $filters): Collection
    {
        $cacheKey = self::CACHE_PREFIX . "top_categories:{$groupId}:" . md5(serialize($filters->toArray()));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($groupId, $filters) {
            return DB::table('expenses')
                ->join('categories', 'expenses.category_id', '=', 'categories.id')
                ->where('expenses.group_id', $groupId)
                ->when($filters->startDate, function ($query, $startDate) {
                    $query->where('expenses.date', '>=', $startDate);
                })
                ->when($filters->endDate, function ($query, $endDate) {
                    $query->where('expenses.date', '<=', $endDate);
                })
                ->selectRaw('categories.name as category_name,
                           categories.icon as category_icon,
                           SUM(expenses.amount) as total_amount,
                           COUNT(*) as expenses_count,
                           AVG(expenses.amount) as average_amount')
                ->groupBy('categories.id', 'categories.name', 'categories.icon')
                ->orderByDesc('total_amount')
                ->limit($filters->limit ?? 10)
                ->get()
                ->map(function ($item) {
                    return [
                        'category_name' => $item->category_name,
                        'category_icon' => $item->category_icon,
                        'total_amount' => (float) $item->total_amount,
                        'expenses_count' => (int) $item->expenses_count,
                        'average_amount' => (float) $item->average_amount,
                        'percentage' => 0
                    ];
                });
        });
    }

    public function getUserSpendingStats(string $groupId, AnalyticsFilterDTO $filters): Collection
    {
        $cacheKey = self::CACHE_PREFIX . "user_stats:{$groupId}:" . md5(serialize($filters->toArray()));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($groupId, $filters) {
            $userSpending = DB::table('expenses')
                ->join('users', 'expenses.payer_id', '=', 'users.id')
                ->where('expenses.group_id', $groupId)
                ->when($filters->startDate, function ($query, $startDate) {
                    $query->where('expenses.date', '>=', $startDate);
                })
                ->when($filters->endDate, function ($query, $endDate) {
                    $query->where('expenses.date', '<=', $endDate);
                })
                ->selectRaw('users.id as user_id,
                           CONCAT(users.first_name, " ", COALESCE(users.last_name, "")) as user_name,
                           SUM(expenses.amount) as total_spent,
                           COUNT(*) as expenses_count,
                           AVG(expenses.amount) as average_expense')
                ->groupBy('users.id', 'users.first_name', 'users.last_name')
                ->get();

            $totalGroupSpent = $userSpending->sum('total_spent');

            return $userSpending->map(function ($item) use ($totalGroupSpent) {
                return new UserSpendingDTO(
                    userId: $item->user_id,
                    userName: $item->user_name,
                    totalSpent: (float) $item->total_spent,
                    totalReceived: 0,
                    netBalance: (float) $item->total_spent,
                    expensesCount: (int) $item->expenses_count,
                    averageExpense: (float) $item->average_expense
                );
            })->sortByDesc('totalSpent');
        });
    }

    public function getPeriodComparison(string $groupId, AnalyticsFilterDTO $filters): array
    {
        $cacheKey = self::CACHE_PREFIX . "period_comparison:{$groupId}:" . md5(serialize($filters->toArray()));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($groupId, $filters) {
            $currentPeriod = $this->buildBaseQuery($groupId, $filters);
            $previousPeriod = $this->buildBaseQuery($groupId, $filters);

            $currentData = $currentPeriod->selectRaw('SUM(amount) as total_amount, COUNT(*) as expenses_count')->first();
            $previousData = $previousPeriod->where('date', '<', $filters->startDate)->first();

            $currentAmount = (float) ($currentData->total_amount ?? 0);
            $currentCount = (int) ($currentData->expenses_count ?? 0);
            $previousAmount = (float) ($previousData->total_amount ?? 0);
            $previousCount = (int) ($previousData->expenses_count ?? 0);

            $amountChange = $previousAmount > 0 ? (($currentAmount - $previousAmount) / $previousAmount) * 100 : 0;
            $countChange = $previousCount > 0 ? (($currentCount - $previousCount) / $previousCount) * 100 : 0;

            return [
                'current_period' => [
                    'total_amount' => $currentAmount,
                    'expenses_count' => $currentCount,
                    'average_amount' => $currentCount > 0 ? $currentAmount / $currentCount : 0
                ],
                'previous_period' => [
                    'total_amount' => $previousAmount,
                    'expenses_count' => $previousCount,
                    'average_amount' => $previousCount > 0 ? $previousAmount / $previousCount : 0
                ],
                'changes' => [
                    'amount_change_percentage' => round($amountChange, 2),
                    'count_change_percentage' => round($countChange, 2),
                    'is_amount_increase' => $amountChange > 0,
                    'is_count_increase' => $countChange > 0
                ]
            ];
        });
    }

    public function getSavingsOpportunities(string $groupId, AnalyticsFilterDTO $filters): array
    {
        $cacheKey = self::CACHE_PREFIX . "savings_opportunities:{$groupId}:" . md5(serialize($filters->toArray()));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($groupId, $filters) {
            $recurringExpenses = $this->findRecurringExpenses($groupId, $filters);
            $highFrequencyCategories = $this->findHighFrequencyCategories($groupId, $filters);
            $expensiveHabits = $this->findExpensiveHabits($groupId, $filters);

            return [
                'recurring_expenses' => $recurringExpenses,
                'high_frequency_categories' => $highFrequencyCategories,
                'expensive_habits' => $expensiveHabits,
                'total_potential_savings' => array_sum(array_column($recurringExpenses, 'potential_savings')) +
                                             array_sum(array_column($highFrequencyCategories, 'potential_savings')) +
                                             array_sum(array_column($expensiveHabits, 'potential_savings'))
            ];
        });
    }

    public function getSpendingPredictions(string $groupId, AnalyticsFilterDTO $filters): array
    {
        $cacheKey = self::CACHE_PREFIX . "spending_predictions:{$groupId}:" . md5(serialize($filters->toArray()));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($groupId, $filters) {
            $historicalData = $this->getHistoricalSpendingData($groupId, $filters);
            $seasonalPatterns = $this->analyzeSeasonalPatterns($historicalData);
            $categoryTrends = $this->analyzeCategoryTrends($groupId, $filters);

            return [
                'next_month_prediction' => $this->predictNextMonthSpending($historicalData),
                'category_predictions' => $this->predictCategorySpending($categoryTrends),
                'seasonal_patterns' => $seasonalPatterns,
                'confidence_level' => $this->calculateConfidenceLevel($historicalData)
            ];
        });
    }

    public function getGroupAnalyticsDashboard(string $groupId, AnalyticsFilterDTO $filters): array
    {
        $cacheKey = self::CACHE_PREFIX . "dashboard:{$groupId}:" . md5(serialize($filters->toArray()));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($groupId, $filters) {
            return [
                'spending_trend' => $this->getGroupSpendingTrend($groupId, $filters),
                'category_breakdown' => $this->getCategoryBreakdown($groupId, $filters),
                'user_comparison' => $this->getUserSpendingComparison($groupId, $filters),
                'period_comparison' => $this->getPeriodComparison($groupId, $filters),
                'top_categories' => $this->getTopSpendingCategories($groupId, $filters),
                'user_stats' => $this->getUserSpendingStats($groupId, $filters),
                'savings_opportunities' => $this->getSavingsOpportunities($groupId, $filters),
                'spending_predictions' => $this->getSpendingPredictions($groupId, $filters)
            ];
        });
    }

    public function getUserAnalyticsDashboard(User $user, AnalyticsFilterDTO $filters): array
    {
        $cacheKey = self::CACHE_PREFIX . "user_dashboard:{$user->id}:" . md5(serialize($filters->toArray()));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $filters) {
            $userGroups = $user->groups()->pluck('groups.id');

            $userSpending = DB::table('expenses')
                ->whereIn('group_id', $userGroups)
                ->where('payer_id', $user->id)
                ->when($filters->startDate, function ($query, $startDate) {
                    $query->where('date', '>=', $startDate);
                })
                ->when($filters->endDate, function ($query, $endDate) {
                    $query->where('date', '<=', $endDate);
                })
                ->selectRaw('SUM(amount) as total_spent, COUNT(*) as expenses_count')
                ->first();

            $categoryBreakdown = DB::table('expenses')
                ->join('categories', 'expenses.category_id', '=', 'categories.id')
                ->whereIn('expenses.group_id', $userGroups)
                ->where('expenses.payer_id', $user->id)
                ->when($filters->startDate, function ($query, $startDate) {
                    $query->where('expenses.date', '>=', $startDate);
                })
                ->when($filters->endDate, function ($query, $endDate) {
                    $query->where('expenses.date', '<=', $endDate);
                })
                ->selectRaw('categories.name as category_name, SUM(expenses.amount) as total_amount')
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('total_amount')
                ->limit(5)
                ->get();

            return [
                'total_spent' => (float) ($userSpending->total_spent ?? 0),
                'expenses_count' => (int) ($userSpending->expenses_count ?? 0),
                'average_expense' => ($userSpending->expenses_count ?? 0) > 0 ? 
                    (float) ($userSpending->total_spent ?? 0) / ($userSpending->expenses_count ?? 1) : 0,
                'category_breakdown' => $categoryBreakdown,
                'groups_breakdown' => $this->getUserGroupsBreakdown($user, $filters)
            ];
        });
    }

    public function generateAnalyticsReport(string $groupId, AnalyticsFilterDTO $filters): string
    {
        $dashboard = $this->getGroupAnalyticsDashboard($groupId, $filters);
        
        return $this->formatReport($dashboard, $filters);
    }

    private function buildBaseQuery(string $groupId, AnalyticsFilterDTO $filters)
    {
        return Expense::where('group_id', $groupId)
            ->when($filters->startDate, function ($query, $startDate) {
                $query->where('date', '>=', $startDate);
            })
            ->when($filters->endDate, function ($query, $endDate) {
                $query->where('date', '<=', $endDate);
            })
            ->when($filters->categoryIds, function ($query, $categoryIds) {
                $query->whereIn('category_id', $categoryIds);
            })
            ->when($filters->userIds, function ($query, $userIds) {
                $query->whereIn('payer_id', $userIds);
            });
    }

    private function getPeriodSelect(string $period): string
    {
        return match($period) {
            'daily' => "DATE(date)",
            'weekly' => "YEARWEEK(date)",
            'monthly' => "DATE_FORMAT(date, '%Y-%m')",
            'yearly' => "YEAR(date)",
            default => "DATE_FORMAT(date, '%Y-%m')"
        };
    }

    private function darkenColor($color, $amount = 20)
    {
        $color = str_replace('#', '', $color);
        if (strlen($color) != 6) return $color;
        
        $rgb = [
            hexdec(substr($color, 0, 2)),
            hexdec(substr($color, 2, 2)),
            hexdec(substr($color, 4, 2))
        ];
        
        $rgb = array_map(fn($c) => max(0, $c - $amount), $rgb);
        
        return '#' . sprintf("%02x%02x%02x", $rgb[0], $rgb[1], $rgb[2]);
    }

    private function findRecurringExpenses(string $groupId, AnalyticsFilterDTO $filters): array
    {
        return [];
    }

    private function findHighFrequencyCategories(string $groupId, AnalyticsFilterDTO $filters): array
    {
        return [];
    }

    private function findExpensiveHabits(string $groupId, AnalyticsFilterDTO $filters): array
    {
        return [];
    }

    private function getHistoricalSpendingData(string $groupId, AnalyticsFilterDTO $filters): array
    {
        return [];
    }

    private function analyzeSeasonalPatterns(array $historicalData): array
    {
        return [];
    }

    private function analyzeCategoryTrends(string $groupId, AnalyticsFilterDTO $filters): array
    {
        return [];
    }

    private function predictNextMonthSpending(array $historicalData): float
    {
        return 0.0;
    }

    private function predictCategorySpending(array $categoryTrends): array
    {
        return [];
    }

    private function calculateConfidenceLevel(array $historicalData): float
    {
        return 0.0;
    }

    private function getUserGroupsBreakdown(User $user, AnalyticsFilterDTO $filters): array
    {
        return [];
    }

    private function formatReport(array $dashboard, AnalyticsFilterDTO $filters): string
    {
        return json_encode($dashboard, JSON_PRETTY_PRINT);
    }
}