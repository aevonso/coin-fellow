<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\BalanceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;





Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('telegram', [AuthController::class, 'telegramAuth']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    
    Route::middleware('jwt.auth')->group(function () { 
        Route::get('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});


Route::middleware('jwt.auth')->prefix('groups')->group(function () { 
    Route::get('/', [GroupController::class, 'index']);
    Route::post('/', [GroupController::class, 'store']);
    Route::get('{groupId}', [GroupController::class, 'show']);
    Route::put('{groupId}', [GroupController::class, 'update']);
    Route::delete('{groupId}', [GroupController::class, 'destroy']);
    
    Route::post('{groupId}/invite', [GroupController::class, 'invite']);
    Route::delete('{groupId}/members/{userId}', [GroupController::class, 'removeUser']);
    Route::post('{groupId}/leave', [GroupController::class, 'leave']);
});

//expenses routes
Route::middleware('jwt.auth')->prefix('groups/{groupId}')->group(function () {
    Route::get('expenses', [ExpenseController::class, 'index']);
    Route::post('expenses', [ExpenseController::class, 'store']);
    Route::get('expenses/{expenseId}', [ExpenseController::class, 'show']);
    Route::put('expenses/{expenseId}', [ExpenseController::class, 'update']);
    Route::delete('expenses/{expenseId}', [ExpenseController::class, 'destroy']);
});

//user expenses (из всех групп)
Route::middleware('jwt.auth')->get('/user/expenses', [ExpenseController::class, 'userExpenses']);


//balances

Route::middleware('jwt.auth')->prefix('groups/{groupId}')->group(function () {
    Route::get('balances', [BalanceController::class, 'getGroupBalances']);
    Route::get('balances/simplified', [BalanceController::class, 'getSimplifiedDebts']);
    Route::get('balances/my', [BalanceController::class, 'getUserBalances']);
    Route::get('balances/summary', [BalanceController::class, 'getBalanceSummary']);
    Route::post('balances/recalculate', [BalanceController::class, 'recalculateBalances']);
    Route::post('balances/settle', [BalanceController::class, 'settleDebt']);
});


//categories

Route::middleware('jwt.auth')->prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/all', [CategoryController::class, 'listAll']);
    Route::post('/', [CategoryController::class, 'store']);
    Route::get('/user-statistics', [CategoryController::class, 'userStatistics']);
    
    Route::prefix('{categoryId}')->group(function () {
        Route::get('/', [CategoryController::class, 'show']);
        Route::put('/', [CategoryController::class, 'update']);
        Route::delete('/', [CategoryController::class, 'destroy']);
        Route::get('/statistics', [CategoryController::class, 'statistics']);
    });
});