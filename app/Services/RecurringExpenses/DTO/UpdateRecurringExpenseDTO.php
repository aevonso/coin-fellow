<?php

namespace App\Services\RecurringExpenses\DTO;

use Spatie\LaravelData\Data;

class UpdateRecurringExpenseDTO extends Data
{
    public function __construct(
        public ?string $description = null,
        public ?float $amount = null,
        public ?string $frequency = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $categoryId = null,
        public ?int $dayOfMonth = null,
        public ?array $weekdays = null,
        public ?bool $isActive = null,
        public ?array $participants = null
    ) {}
}