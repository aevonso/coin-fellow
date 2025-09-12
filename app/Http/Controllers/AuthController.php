<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\TelegramAuthRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use App\Services\Auth\DTO\LoginDTO;
use App\Services\Auth\DTO\RegisterDTO;
use App\Services\Auth\DTO\TelegramAuthDTO;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function register(RegisterRequest $request): AuthResource
    {
        $dto = RegisterDTO::from($request->validated());
        $tokens = $this->authService->register($dto);

        return new AuthResource($tokens);
    }

    public function login(LoginRequest $request): AuthResource
    {
        $dto = LoginDTO::from($request->validated());
        $tokens = $this->authService->login($dto);

        return new AuthResource($tokens);
    }

    public function telegramAuth(TelegramAuthRequest $request): AuthResource
    {
        $dto = TelegramAuthDTO::from($request->validated());
        $tokens = $this->authService->telegramAuth($dto);

        return new AuthResource($tokens);
    }

    public function refresh(): AuthResource
    {
        $tokens = $this->authService->refreshToken();
        return new AuthResource($tokens);
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out',
        ]);
    }

    public function me(): UserResource
    {
        $user = $this->authService->getAuthenticatedUser();
        return new UserResource($user);
    }
}