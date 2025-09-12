<?php

namespace App\Services\Auth\Interfaces;

use App\Services\Auth\DTO\LoginDTO;
use App\Services\Auth\DTO\RegisterDTO;
use App\Services\Auth\DTO\TelegramAuthDTO;
use App\Models\User;

interface AuthServiceInterface {
    public function register(RegisterDTO $dto): array;
    public function login(LoginDTO $dto): array;
    public function refreshToken():array;
    public function logout(): void;
    public function getAuthenticatedUser(): User;
}