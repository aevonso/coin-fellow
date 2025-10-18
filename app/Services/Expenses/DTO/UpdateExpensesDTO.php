<?php

namespace App\Services\Expenses\DTO;

use Spatie\LaravelData\Data;

class UpdateExpenseDTO extends Data {
    public function __construct (
        public ?string $description = null,
        public ?float $amount = null,
        public ?string $date = null,
        public ?string $categoryId = null,
        public ?string $participants = null
    ) {}
}