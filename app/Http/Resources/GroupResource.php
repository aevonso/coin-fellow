<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class GroupResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'currency' => $this->currency,
            'description' => $this->description,
            'invite_code' => $this->invite_code,
            'members_count' => $this->whenCounted('users', $this->users_count),
            'total_expenses' => $this->when(isset($this->expenses_sum_amount), 
                (float) $this->expenses_sum_amount),
            'user_role' => $this->whenPivotLoaded('group_user', function () {
                return $this->pivot->role;
            }),
            'is_owner' => $this->when($request->user(), function () use ($request) {
                return $this->isUserOwner($request->user());
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'members' => UserResource::collection($this->whenLoaded('users')),
            'expenses' => ExpenseResource::collection($this->whenLoaded('expenses')),
            'recent_expenses' => ExpenseResource::collection(
                $this->whenLoaded('expenses', function () {
                    return $this->expenses->take(5);
                })
            ),
        ];
    }

    public function with(Request $request): array {
        return [
            'success' => true,
        ];
    }
}