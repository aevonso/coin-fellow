<?php
namespace App\Services\Auth\DTO;

use Spatie\LaravelData\Data;

class TelegramAuthDTO extends Data {
    public function __construct (
        public ?string $telegram_user_id,
        public ?string $username = null,
        public ?string $first_name = null,
        public ?string $last_name = null,
        public ?string $language_code = null
    ) {}
}