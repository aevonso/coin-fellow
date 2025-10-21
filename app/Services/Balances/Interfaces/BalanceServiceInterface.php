<?php

namespace App\Services\Balances\Interfaces;

use App\Models\Balance;
use App\Models\User;
use App\Services\Balances\DTO\RecalculateBalancesDTO;
use App\Services\Balances\DTO\SettlementDTO;
use Illuminate\Support\Collection;

interface BalanceServiceInterface {
    public function calculateBalancesForGroup(string $groupId): void;
    public function getGroupBalances(string $groupId): Collection;
    public function getUserBalancesInGroup(string $groupId, string $userId): Collection;
    public function getSimplifiedDebts(string $groupId): Collection;
    public function recalculateBalances(RecalculateBalancesDTO $dto): void;
    public function settleDebt(SettlementDTO $dto): void;
    public function getBalanceSummary(string $groupId, string $userId): array;

}