<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChartDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'labels' => $this->labels,
            'datasets' => $this->datasets,
            'metadata' => $this->metadata
        ];
    }
}