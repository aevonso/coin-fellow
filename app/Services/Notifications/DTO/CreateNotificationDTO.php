<?php

namespace App\Services\Notifications\DTO;

use Spatie\LaravelData\Data;

class CreateNotificationDTO extends Data
{
    public function __construct(
        public string $userId,
        public string $type,
        public string $message,
        public ?string $groupId = null,
        public ?array $data = null
    ) {}
}