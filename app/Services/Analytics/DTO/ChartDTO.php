<?php

namespace App\Services\Analytics\DTO;

use Spatie\LaravelData\Data;

class ChartDTO extends Data {
    public function __construct(
        public array $labels,
        public array $datases,
        public array $metadata = []
    ) {}
}
