<?php

namespace App\Services\RecurringExpenses\DTO;

use Spatie\LaravelData\Data;

class CreateRecurringExpenseDTO extends Data
{
    public function __construct(
        public string $groupId,
        public string $payerId,
        public string $description,
        public float $amount,
        public string $frequency,
        public string $startDate,
        public ?string $categoryId = null,
        public ?string $endDate = null,
        public ?int $dayOfMonth = null,
        public ?array $weekdays = null,
        public ?array $participants = null
    ) {}
}