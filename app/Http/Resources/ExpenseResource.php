<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'date' => $this->date->format('Y-m-d'),
            'amount_per_participant' => $this->when($this->participants_count, 
                (float) $this->getAmountPerParticipant()),
            
            'payer' => new UserResource($this->whenLoaded('payer')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'participants' => UserResource::collection($this->whenLoaded('participants')),
            'participants_count' => $this->whenCounted('participants', $this->participants_count),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}