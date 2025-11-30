<?php

namespace App\Services\Budgets\DTO;

use Spatie\LaravelData\Data;

class CreateBudgetDTO extends Data
{
    public function __construct(
        public string $groupId,
        public float $amount,
        public string $period,
        public string $startDate,
        public ?string $categoryId = null,
        public ?string $userId = null,
        public ?string $endDate = null,
        public ?int $notifyOnPercentage = 80,
        public ?string $currency = 'RUB'
    ) {}
}