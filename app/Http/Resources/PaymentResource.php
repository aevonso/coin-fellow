<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'date' => $this->date->format('Y-m-d'),
            'status' => $this->status,
            'notes' => $this->notes,
            
         
            'from_user' => new UserResource($this->whenLoaded('fromUser')),
            'to_user' => new UserResource($this->whenLoaded('toUser')),
            'group' => new GroupResource($this->whenLoaded('group')),
            
            
            'is_pending' => $this->isPending(),
            'is_confirmed' => $this->isConfirmed(),
            'can_confirm' => $this->when(auth()->check(), $this->canBeConfirmed()),
            'can_cancel' => $this->when(auth()->check(), $this->canBeCancelled()),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}