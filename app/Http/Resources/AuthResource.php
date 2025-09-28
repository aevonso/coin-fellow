<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this['access_token'],
            'refresh_token' => $this['refresh_token'],
            'token_type' => $this['token_type'],
            'expires_in' => $this['expires_in'],
            'user' => $this['user'] ?? null,
        ];
    }

    public function with(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Authentication successful',
        ];
    }
}