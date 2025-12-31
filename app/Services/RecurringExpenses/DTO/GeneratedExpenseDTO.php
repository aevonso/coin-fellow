<?php

namespace App\Services\RecurringExpenses\DTO;

use Spatie\LaravelData\Data;

class GeneratedExpenseDTO extends Data
{
    public function __construct(
        public string $recurringExpenseId,
        public string $description,
        public float $amount,
        public string $date,
        public string $groupId,
        public string $payerId,
        public ?string $categoryId = null,
        public ?array $participants = null
    ) {}
}