<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon' => $this->icon,
            'color' => $this->color,
            'is_default' => $this->is_default,
            'user_id' => $this->user_id,
            'is_editable' => $this->when($this->is_default !== null, $this->isEditable()),
            'is_deletable' => $this->when($this->is_default !== null, $this->isDeletable()),
            'expenses_count' => $this->whenCounted('expenses', $this->expenses_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}