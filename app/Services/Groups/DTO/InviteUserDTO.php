<?php

namespace App\Services\Groups\DTO;
use Spatie\LaravelData\Data;

class InviteUserDTO extends Data 
{
    public function __construct (
        public string $email_or_username,
        public ?string $role = 'member'
    ) {}
}