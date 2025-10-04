<?php

namespace App\Services\Groups\DTO;

use Spatie\LaravelData\Data;

class UpdateGroupDTO extends Data 
{
    public function __construct (
        public ?string $name = null,
        public ?string $currency = null,
        public ?string $description = null
    ) {}
}