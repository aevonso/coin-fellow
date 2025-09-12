<?php

namespace App\Services\Auth\DTO;

use Spatie\LaravelData\Data;

class RegisterDTO extends Data
{
    public function __construct(
        public ?string $email,
        public ?string $phone,
        public string $password,
        public ?string $first_name = null,
        public ?string $last_name = null,
        public ?string $username = null
    ) {}
}