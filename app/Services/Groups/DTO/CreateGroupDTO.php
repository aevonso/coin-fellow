<?php

namespace App\Services\Groups\DTO;
use Spatie\LaravelData\Data;

class CreateGroupDTO extends Data 
{
    public function __construct(
        public string $name,
        public string $currency, 
        public ?string $description = null
    ) {}
}