<?php

namespace App\Services\Balances\DTO;

use Spatie\LaravelData\Data;

class SettlementDTO extends Data {
    public function __construct (
        public string $groupId,
        public string $fromUserId,
        public string $toUserId,
        public float $amount,
        public ?string $description = null
    ) {}
}