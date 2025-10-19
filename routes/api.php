<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ExpenseController;
use Illuminate\Support\Facades\Route;





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