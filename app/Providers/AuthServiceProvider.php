<?php

namespace App\Providers;

use App\Services\Auth\AuthService;
use App\Services\Groups\GroupService;
use App\Services\Expenses\ExpenseService;
use App\Services\Auth\Interfaces\AuthServiceInterface;
use App\Services\Groups\Interfaces\GroupServiceInterface;
use App\Services\Expenses\Interfaces\ExpenseServiceInterface;
use App\Services\JWT\JWTService;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(GroupServiceInterface::class, GroupService::class);
        $this->app->bind(ExpenseServiceInterface::class, ExpenseService::class);
        $this->app->bind(JWTService::class, function () {
            return new JWTService();
        });
    }

    public function boot(): void
    {
        //
    }
}