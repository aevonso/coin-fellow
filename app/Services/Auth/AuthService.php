<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\JWT\JWTService;
use App\Services\Auth\DTO\LoginDTO;
use App\Services\Auth\DTO\RegisterDTO;
use App\Services\Auth\DTO\TelegramAuthDTO;
use App\Services\Auth\Interfaces\AuthServiceInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private JWTService $jwtService
    ) {}

    public function register(RegisterDTO $dto): array
    {
        $user = User::create([
            'email' => $dto->email,
            'phone' => $dto->phone,
            'password' => Hash::make($dto->password),
            'first_name' => $dto->first_name,
            'last_name' => $dto->last_name,
            'username' => $dto->username,
        ]);

        $tokens =  $this->jwtService->generateTokens($user);

        return array_merge($tokens, ['user' => $user]);
    }

    public function login(LoginDTO $dto): array
    {
        $user = User::where('email', $dto->login)
            ->orWhere('phone', $dto->login)
            ->first();

        if (!$user || !Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Неверные учетные данные'],
            ]);
        }

        $user->update(['last_login_at' => now()]);

        $tokens = $this->jwtService->generateTokens($user);

        return array_merge($tokens, ['user' => $user]);
    }

    public function telegramAuth(TelegramAuthDTO $dto): array
    {
        $user = User::firstOrCreate(
            ['telegram_user_id' => $dto->telegram_user_id],
            [
                'username' => $dto->username,
                'first_name' => $dto->first_name,
                'last_name' => $dto->last_name,
                'language_code' => $dto->language_code,
                'avatar_url' => $dto->avatar_url,
                'telegram_verified_at' => now(),
            ]
        );

        $user->update(['last_login_at' => now()]);

        $tokens = $this->jwtService->generateTokens($user);

        return array_merge($tokens, ['user' => $user]);
    }

  public function refreshToken(): array
    {
        $refreshToken = request()->input('refresh_token');
        return $this->jwtService->refreshTokens($refreshToken);
    }

    public function logout(): void
    {
        $token = JWTAuth::getToken();
        if ($token) {
            JWTAuth::invalidate($token);
            
            $user = auth()->user();
            if ($user) {
                $user->update([
                    'refresh_token' => null,
                    'refresh_token_expires_at' => null,
                ]);
            }
        }
    }

    public function getAuthenticatedUser(): User
    {
        $user = auth()->user();
        if (!$user) {
            throw new \Exception('User not authenticated');
        }
        return $user;
    }
}