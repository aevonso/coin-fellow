<?php

namespace App\Services\Balances\DTO;

use spatie\LaravelData\Data;

class RecalculateBalancesDTO extends Data {
    public function __construct (
        public string $groupId
    ) {}
}