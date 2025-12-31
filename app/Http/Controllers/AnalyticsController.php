<?php

namespace App\Http\Controllers;

use App\Http\Requests\Analytics\AnalyticsRequest;
use App\Http\Resources\ChartDataResource;
use App\Services\Analytics\DTO\AnalyticsFilterDTO;
use App\Services\Analytics\Interfaces\AnalyticsServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsServiceInterface $analyticsService
    ) {}

    public function getSpendingTrend(AnalyticsRequest $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->checkGroupAccess($user, $groupId);
        
        $dto = AnalyticsFilterDTO::from($request->validated());
        $chartData = $this->analyticsService->getGroupSpendingTrend($groupId, $dto);

        return response()->json([
            'success' => true,
            'data' => new ChartDataResource($chartData),
            'message' => 'Тенденции расходов успешно получены'
        ]);
    }

    public function getCategoryBreakdown(AnalyticsRequest $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->checkGroupAccess($user, $groupId);
        
        $dto = AnalyticsFilterDTO::from($request->validated());
        $chartData = $this->analyticsService->getCategoryBreakdown($groupId, $dto);

        return response()->json([
            'success' => true,
            'data' => new ChartDataResource($chartData),
            'message' => 'Разбивка по категориям успешно получена'
        ]);
    }

    public function getUserSpendingComparison(AnalyticsRequest $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->checkGroupAccess($user, $groupId);
        
        $dto = AnalyticsFilterDTO::from($request->validated());
        $chartData = $this->analyticsService->getUserSpendingComparison($groupId, $dto);

        return response()->json([
            'success' => true,
            'data' => new ChartDataResource($chartData),
            'message' => 'Сравнение расходов пользователей успешно получено'
        ]);
    }

    public function getExpenseDistribution(AnalyticsRequest $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->checkGroupAccess($user, $groupId);
        
        $dto = AnalyticsFilterDTO::from($request->validated());
        $chartData = $this->analyticsService->getExpenseDistribution($groupId, $dto);

        return response()->json([
            'success' => true,
            'data' => new ChartDataResource($chartData),
            'message' => 'Распределение расходов успешно получено'
        ]);
    }

    public function getTopSpendingCategories(AnalyticsRequest $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->checkGroupAccess($user, $groupId);
        
        $dto = AnalyticsFilterDTO::from($request->validated());
        $categories = $this->analyticsService->getTopSpendingCategories($groupId, $dto);

        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Успешно получены данные по основным категориям расходов'
        ]);
    }

    public function getUserSpendingStats(AnalyticsRequest $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->checkGroupAccess($user, $groupId);
        
        $dto = AnalyticsFilterDTO::from($request->validated());
        $stats = $this->analyticsService->getUserSpendingStats($groupId, $dto);

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Статистика расходов пользователей успешно получена'
        ]);
    }

    public function getPeriodComparison(AnalyticsRequest $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->checkGroupAccess($user, $groupId);
        
        $dto = AnalyticsFilterDTO::from($request->validated());
        $comparison = $this->analyticsService->getPeriodComparison($groupId, $dto);

        return response()->json([
            'success' => true,
            'data' => $comparison,
            'message' => 'Сравнение периодов успешно получено'
        ]);
    }

    public function getSavingsOpportunities(AnalyticsRequest $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->checkGroupAccess($user, $groupId);
        
        $dto = AnalyticsFilterDTO::from($request->validated());
        $opportunities = $this->analyticsService->getSavingsOpportunities($groupId, $dto);

        return response()->json([
            'success' => true,
            'data' => $opportunities,
            'message' => 'Возможности экономии успешно использованы'
        ]);
    }

    public function getSpendingPredictions(AnalyticsRequest $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->checkGroupAccess($user, $groupId);
        
        $dto = AnalyticsFilterDTO::from($request->validated());
        $predictions = $this->analyticsService->getSpendingPredictions($groupId, $dto);

        return response()->json([
            'success' => true,
            'data' => $predictions,
            'message' => 'Прогнозы расходов успешно получены'
        ]);
    }

    public function getGroupAnalyticsDashboard(AnalyticsRequest $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->checkGroupAccess($user, $groupId);
        
        $dto = AnalyticsFilterDTO::from($request->validated());
        $dashboard = $this->analyticsService->getGroupAnalyticsDashboard($groupId, $dto);

        return response()->json([
            'success' => true,
            'data' => $dashboard,
            'message' => 'Панель мониторинга групповой аналитики успешно получена'
        ]);
    }

    public function getUserAnalyticsDashboard(AnalyticsRequest $request): JsonResponse
    {
        $user = $request->user();
        $dto = AnalyticsFilterDTO::from($request->validated());
        $dashboard = $this->analyticsService->getUserAnalyticsDashboard($user, $dto);

        return response()->json([
            'success' => true,
            'data' => $dashboard,
            'message' => 'Панель аналитики пользователей успешно получена'
        ]);
    }

    public function generateReport(AnalyticsRequest $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->checkGroupAccess($user, $groupId);
        
        $dto = AnalyticsFilterDTO::from($request->validated());
        $report = $this->analyticsService->generateAnalyticsReport($groupId, $dto);

        return response()->json([
            'success' => true,
            'data' => [
                'report' => $report,
                'format' => 'json'
            ],
            'message' => 'Аналитический отчет успешно создан'
        ]);
    }

    private function checkGroupAccess(User $user, string $groupId): void
    {
        $group = \App\Models\Group::findOrFail($groupId);
        
        if (!$group->users->contains($user->id)) {
            abort(403, 'Вы не являетесь участником этой группы');
        }
    }
}