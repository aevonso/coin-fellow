<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'period' => $this->period,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'is_active' => $this->is_active,
            'notify_on_percentage' => $this->notify_on_percentage,
            'currency' => $this->currency,
            
            'spent_amount' => $this->when($this->spent_amount !== null, (float) $this->spent_amount),
            'remaining_amount' => $this->when($this->remaining_amount !== null, (float) $this->remaining_amount),
            'usage_percentage' => $this->when($this->usage_percentage !== null, (float) $this->usage_percentage),
            'is_exceeded' => $this->when($this->is_exceeded !== null, $this->is_exceeded),
            
            'category' => new CategoryResource($this->whenLoaded('category')),
            'user' => new UserResource($this->whenLoaded('user')),
            'group' => new GroupResource($this->whenLoaded('group')),
            
            'is_current' => $this->when($this->start_date !== null, $this->isCurrent()),
            'remaining_days' => $this->when($this->end_date !== null, now()->diffInDays($this->end_date)),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}