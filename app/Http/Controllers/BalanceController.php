<?php

namespace App\Http\Controllers;

use App\Http\Requests\Balances\RecalculateBalancesRequest;
use App\Http\Requests\Balances\SettlementRequest;
use App\Http\Resources\BalanceResource;
use App\Http\Resources\Collections\BalanceCollection;
use App\Models\Group;
use App\Services\Balances\DTO\RecalculateBalancesDTO;
use App\Services\Balances\DTO\SettlementDTO;
use App\Services\Balances\Interfaces\BalanceServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function __construct(
        private BalanceServiceInterface $balanceService
    ) {}

    public function getGroupBalances(Request $request, string $groupId): BalanceCollection
    {
        $user = $request->user();
        $this->checkGroupMembership($user, $groupId);
        
        $balances = $this->balanceService->getGroupBalances($groupId);

        return new BalanceCollection($balances);
    }

    public function getSimplifiedDebts(Request $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->checkGroupMembership($user, $groupId);
        
        $debts = $this->balanceService->getSimplifiedDebts($groupId);

        return response()->json([
            'success' => true,
            'data' => $debts,
            'message' => 'Упрощенный процесс успешного взыскания долгов'
        ]);
    }

    public function getUserBalances(Request $request, string $groupId): BalanceCollection
    {
        $user = $request->user();
        $this->checkGroupMembership($user, $groupId);
        
        $balances = $this->balanceService->getUserBalancesInGroup($groupId, $user->id);

        return new BalanceCollection($balances);
    }

    public function getBalanceSummary(Request $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->checkGroupMembership($user, $groupId);
        
        $summary = $this->balanceService->getBalanceSummary($groupId, $user->id);

        return response()->json([
            'success' => true,
            'data' => $summary,
            'message' => 'Успешно восстановлена сводная информация о балансе'
        ]);
    }

    public function recalculateBalances(RecalculateBalancesRequest $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $group = $this->checkGroupMembership($user, $groupId);
        
        if (!$group->isUserAdmin($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Только администраторы групп могут пересчитывать балансы'
            ], 403);
        }

        $dto = RecalculateBalancesDTO::from(['groupId' => $groupId]);
        $this->balanceService->recalculateBalances($dto);

        return response()->json([
            'success' => true,
            'message' => 'Балансы успешно пересчитаны'
        ]);
    }

    public function settleDebt(SettlementRequest $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->checkGroupMembership($user, $groupId);

        $validated = $request->validated();
        $validated['groupId'] = $groupId;

        $dto = SettlementDTO::from($validated);
        $this->balanceService->settleDebt($dto);

        return response()->json([
            'success' => true,
            'message' => 'Задолженность успешно погашена'
        ]);
    }

    private function checkGroupMembership(User $user, string $groupId): Group
    {
        $group = Group::with('users')->findOrFail($groupId);
        
        if (!$group->users->contains($user->id)) {
            abort(403, 'Вы не являетесь членом этой группы');
        }
        
        return $group;
    }

    /**
     * Получить мои долги (я должен)
     */
    public function getMyDebts(Request $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->checkGroupMembership($user, $groupId);
        
        $balances = $this->balanceService->getUserBalancesInGroup($groupId, $user->id);
        $debts = $balances->filter(function ($balance) use ($user) {
            return $balance->from_user_id === $user->id;
        });

        return response()->json([
            'success' => true,
            'data' => BalanceResource::collection($debts),
            'message' => 'My debts retrieved successfully'
        ]);
    }

    /**
     * Получить долги мне (мне должны)
     */
    public function getDebtsToMe(Request $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $this->checkGroupMembership($user, $groupId);
        
        $balances = $this->balanceService->getUserBalancesInGroup($groupId, $user->id);
        $credits = $balances->filter(function ($balance) use ($user) {
            return $balance->to_user_id === $user->id;
        });

        return response()->json([
            'success' => true,
            'data' => BalanceResource::collection($credits),
            'message' => 'Debts to me retrieved successfully'
        ]);
    }
}