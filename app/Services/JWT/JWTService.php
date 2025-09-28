<?php

namespace App\Services\JWT;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Str;

class JWTService
{
    public function generateTokens(User $user): array
    {
        $accessToken = JWTAuth::fromUser($user);
        $refreshToken = Str::random(80);
        
        $user->update([
            'refresh_token' => hash('sha256', $refreshToken),
            'refresh_token_expires_at' => now()->addDays(30),
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    public function refreshTokens(string $refreshToken): array
    {
        try {
            $user = User::where('refresh_token', hash('sha256', $refreshToken))
                ->where('refresh_token_expires_at', '>', now())
                ->firstOrFail();

            JWTAuth::invalidate(JWTAuth::getToken());

            return $this->generateTokens($user);
        } catch (JWTException $e) {
            throw new \Exception('Invalid refresh token');
        }
    }

    public function invalidateTokens(User $user): void
    {
        $user->update([
            'refresh_token' => null,
            'refresh_token_expires_at' => null,
        ]);

    }
}