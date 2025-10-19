<?php

namespace App\Services\Expenses\DTO;

use Spatie\LaravelData\Data;

class CreateExpenseDTO extends Data {
    public function __construct (
        public string $description,
        public float $amount,
        public string $date,
        public string $groupId,
        public ?string $categoryId = null,
        public ?array $participants = null
    ) {}
}