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
}