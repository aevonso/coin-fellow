<?php

namespace App\Services\Budgets\DTO;

use Spatie\LaravelData\Data;

class UpdateBudgetDTO extends Data
{
    public function __construct(
        public ?float $amount = null,
        public ?string $period = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?bool $isActive = null,
        public ?int $notifyOnPercentage = null,
        public ?string $currency = null
    ) {}
}