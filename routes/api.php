<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('telegram', [AuthController::class, 'telegramAuth']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    
    Route::middleware('auth:api')->group(function () {
        Route::get('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth.jwt')->prefix('groups')->group(function () {
    Route::get('/', [GroupController::class, 'index']);
    Route::post('/', [GroupController::class, 'store']);
    Route::get('{groupId}', [GroupController::class, 'show']);
    Route::put('{groupId}', [GroupController::class, 'update']);
    Route::delete('{groupId}', [GroupController::class, 'destroy']);
    
    Route::post('{groupId}/invite', [GroupController::class, 'invite']);
    Route::delete('{groupId}/members/{userId}', [GroupController::class, 'removeUser']);
    Route::post('{groupId}/leave', [GroupController::class, 'leave']);
});