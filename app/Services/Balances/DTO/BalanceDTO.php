<?php

namespace App\Services\Balances\DTO;

use Spatie\LaravelData\Data;

class BalanceDTO extends Data {
    public function __construct (
        public string $group_id,
        public string $fromUserId,
        public string $toUserId,
        public float $amount
    ) {}
}