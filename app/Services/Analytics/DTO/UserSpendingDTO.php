<?php

namespace App\Services\Analytics\DTO;

use Spatie\LaravelData\Data;

class UserSpendingDTO extends Data 
{
    public function __construct(
        public string $userId,
        public string $userName,
        public float $totalSpent,
        public float $totalReceived,
        public float $netBalance,
        public int $expensesCount,
        public float $averageExpense
    ) {}
}