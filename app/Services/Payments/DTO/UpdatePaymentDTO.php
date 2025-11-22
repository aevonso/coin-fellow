<?php

namespace App\Services\Payments\DTO;

use Spatie\LaravelData\Data;

class UpdatePaymentDTO extends Data
{
    public function __construct (
        public ?string $status = null,
        public ?string $notes = null
    ) {}
}