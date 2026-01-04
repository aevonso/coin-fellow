<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'currency' => $this->currency,
            'description' => $this->description,
            'invite_code' => $this->invite_code,
            'is_owner' => $this->isUserOwner($request->user()),
            'is_admin' => $this->isUserAdmin($request->user()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'members' => UserResource::collection($this->whenLoaded('members')),
            'members_count' => $this->whenLoaded('members', function () {
                return $this->members->count();
            }),
            'expenses' => ExpenseResource::collection($this->whenLoaded('expenses')),
            'recent_expenses' => ExpenseResource::collection(
                $this->whenLoaded('expenses', function () {
                    return $this->expenses()->latest()->take(5)->get();
                })
            ),
        ];
    }
}