<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Route;

// Test route without middleware
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('telegram', [AuthController::class, 'telegramAuth']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    
    Route::middleware('jwt.auth')->group(function () { // ← используем jwt.auth
        Route::get('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// Groups routes
Route::middleware('jwt.auth')->prefix('groups')->group(function () { // ← используем jwt.auth
    Route::get('/', [GroupController::class, 'index']);
    Route::post('/', [GroupController::class, 'store']);
    Route::get('{groupId}', [GroupController::class, 'show']);
    Route::put('{groupId}', [GroupController::class, 'update']);
    Route::delete('{groupId}', [GroupController::class, 'destroy']);
    
    Route::post('{groupId}/invite', [GroupController::class, 'invite']);
    Route::delete('{groupId}/members/{userId}', [GroupController::class, 'removeUser']);
    Route::post('{groupId}/leave', [GroupController::class, 'leave']);
});