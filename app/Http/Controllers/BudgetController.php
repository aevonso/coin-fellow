<?php

namespace App\Http\Controllers;

use App\Http\Requests\Budgets\CreateBudgetRequest;
use App\Http\Requests\Budgets\UpdateBudgetRequest;
use App\Http\Resources\BudgetResource;
use App\Http\Resources\BudgetStatsResource;
use App\Http\Resources\Collections\BudgetCollection;
use App\Services\Budgets\DTO\CreateBudgetDTO;
use App\Services\Budgets\DTO\UpdateBudgetDTO;
use App\Services\Budgets\Interfaces\BudgetServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(
        private BudgetServiceInterface $budgetService
    ) {}

    public function getGroupBudgets(Request $request, string $groupId): BudgetCollection
    {
        $user = $request->user();
        $budgets = $this->budgetService->getGroupBudgets($user, $groupId);

        return new BudgetCollection($budgets);
    }

    public function createBudget(CreateBudgetRequest $request, string $groupId): BudgetResource
    {
        $user = $request->user();
        $validated = $request->validated();
        $validated['groupId'] = $groupId;
        
        $dto = CreateBudgetDTO::from($validated);
        $budget = $this->budgetService->createBudget($user, $dto);

        return new BudgetResource($budget);
    }

    public function getBudget(Request $request, string $groupId, string $budgetId): BudgetResource
    {
        $user = $request->user();
        $budget = $this->budgetService->getBudget($user, $budgetId);

        return new BudgetResource($budget);
    }

    public function updateBudget(UpdateBudgetRequest $request, string $groupId, string $budgetId): BudgetResource
    {
        $user = $request->user();
        $dto = UpdateBudgetDTO::from($request->validated());
        $budget = $this->budgetService->updateBudget($user, $budgetId, $dto);

        return new BudgetResource($budget);
    }

    public function deleteBudget(Request $request, string $groupId, string $budgetId): JsonResponse
    {
        $user = $request->user();
        $this->budgetService->deleteBudget($user, $budgetId);

        return response()->json([
            'success' => true,
            'message' => 'Бюджет успешно удален',
        ]);
    }

    public function getBudgetStats(Request $request, string $groupId, string $budgetId): JsonResponse
    {
        $user = $request->user();
        $stats = $this->budgetService->getBudgetStats($user, $budgetId);

        return response()->json([
            'success' => true,
            'data' => new BudgetStatsResource($stats),
            'message' => 'Успешно восстановлена бюджетная статистика',
        ]);
    }

    public function getGroupBudgetOverview(Request $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $overview = $this->budgetService->getGroupBudgetOverview($user, $groupId);

        return response()->json([
            'success' => true,
            'data' => $overview,
            'message' => 'Обзор бюджета группы успешно восстановлен',
        ]);
    }

    public function getUserBudgets(Request $request): BudgetCollection
    {
        $user = $request->user();
        $budgets = $this->budgetService->getUserBudgets($user);

        return new BudgetCollection($budgets);
    }

    public function getBudgetHistory(Request $request, string $groupId, string $budgetId): JsonResponse
    {
        $user = $request->user();
        $history = $this->budgetService->getBudgetHistory($user, $budgetId);

        return response()->json([
            'success' => true,
            'data' => $history,
            'message' => 'Успешно восстановлена история бюджета',
        ]);
    }

    public function getBudgetRecommendations(Request $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $recommendations = $this->budgetService->getBudgetRecommendations($user, $groupId);

        return response()->json([
            'success' => true,
            'data' => $recommendations,
            'message' => 'Успешно получены рекомендации по бюджету',
        ]);
    }
}