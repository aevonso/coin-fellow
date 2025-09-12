<?php

namespace App\Services\Auth\DTO;

use Spatie\LaravelData\Data;

class LoginDTO extends Data {
    public function __construct (
        public string $login, //email or phone
        public string $password
    ) {}
}