<?php

namespace App\Services\Analytics\DTO;

use Spatie\LaravelData\Data;

class ExpenseTrendDTO extends Data 
{
    public function __construct(
        public string $period,
        public float $amount,
        public int $count
    ) {}
}