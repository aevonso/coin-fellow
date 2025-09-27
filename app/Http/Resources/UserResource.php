<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url,
            'has_active_subscription' => $this->hasActiveSubscription(),
            'telegram_verified' => (bool) $this->telegram_verified_at,
            'email_verified' => (bool) $this->email_verified_at,
            'phone_verified' => (bool) $this->phone_verified_at,
        ];
    }
}