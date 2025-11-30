<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'message' => $this->message,
            'data' => $this->data,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
        
            'user' => new UserResource($this->whenLoaded('user')),
            'group' => new GroupResource($this->whenLoaded('group')),
            'is_unread' => $this->isUnread(),
            'time_ago' => $this->created_at->diffForHumans(),
        ];
    }
}