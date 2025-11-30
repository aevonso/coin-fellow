<?php

namespace App\Providers;

use App\Services\Auth\AuthService;
use App\Services\Groups\GroupService;
use App\Services\Expenses\ExpenseService;
use App\Services\Expenses\BalanceService;
use App\Services\Categories\CategoryService;
use App\Services\Payments\PaymentService;
use App\Services\Notifications\NotificationService;
use App\Services\Budgets\BudgetService;
use App\Services\Auth\Interfaces\AuthServiceInterface;
use App\Services\Groups\Interfaces\GroupServiceInterface;
use App\Services\Expenses\Interfaces\ExpenseServiceInterface;
use App\Services\Expenses\Interfaces\BalanceServiceInterface;
use App\Services\Categories\Interfaces\CategoryServiceInterface;
use App\Services\Payments\Interfaces\PaymentServiceInterface;
use App\Services\Notifications\Interfaces\NotificationServiceInterface;
use App\Services\Budgets\Interfaces\BudgetServiceInterface;
use App\Services\JWT\JWTService;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(GroupServiceInterface::class, GroupService::class);
        $this->app->bind(ExpenseServiceInterface::class, ExpenseService::class);
        $this->app->bind(BalanceServiceInterface::class, BalanceService::class); 
        $this->app->bind(CategoryServiceInterface::class, CategoryService::class);  
        $this->app->bind(PaymentServiceInterface::class, PaymentService::class);   
        $this->app->bind(NotificationServiceInterface::class, NotificationService::class);  
        $this->app->bind(BudgetServiceInterface::class, BudgetService::class);
        $this->app->bind(JWTService::class, function () {
            return new JWTService();
        });
    }

    public function boot(): void
    {
        //
    }
}