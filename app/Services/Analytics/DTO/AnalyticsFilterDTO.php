<?php

namespace App\Services\Analytics\DTO;

use Spatie\LaravelData\Data;

class AnalyticsFilterDTO extends Data 
{
    public function __construct(
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?array $categoryIds = null,
        public ?array $userIds = null,
        public ?string $period = 'monthly',
        public ?int $limit = 10
    ) {}
}