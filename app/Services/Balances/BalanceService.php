<?php

namespace App\Services\Balances;

use App\Models\Balance;
use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use App\Services\Balances\DTO\RecalculateBalancesDTO;
use App\Services\Balances\DTO\SettlementDTO;
use App\Services\Balances\Interfaces\BalanceServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class BalanceService implements BalanceServiceInterface 
{
    public function calculateBalancesForGroup(string $groupId): void {
        $group = Group::with(['expenses.participants', 'users'])->findOrFail($groupId);

        DB::transaction(function () use ($group){
            Balance::forGroup($group->id)->delete();

            foreach($group->expenses as $expense) {
                $this->calculateExpenseBalances($expense);
            }

            $this->optimizeBalances($group->id);
        });
    }

    private function calculateExpenseBalances(Expense $expense): void {
        $payerId = $expense->payer_id;
        $totalAmount = $expense->amount;
        $participants = $expense->participants;

        if($participants->isEmpty()) return;

        $sharePerParticipant = $totalAmount / $participants->count();

        foreach($participants as $participant) {
            if($participant->id !== $payerId) {
                $this->updateBalance(
                    $expense->group_id,
                    $participant->id,
                    $payerId,
                    $sharePerParticipant
                );
            }
        }
    }

    private function updateBalance(string $groupId, string $fromUserId, string $toUserId, float $amount): void {
        
    }
}