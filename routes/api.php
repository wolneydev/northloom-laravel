<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TelegramSettingsController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public routes: sign-up and login must stay reachable without a token.
Route::post('users', [UserController::class, 'store']);
Route::post('login', [AuthController::class, 'login']);

// Protected routes: everything else requires a valid Passport access token (JWT).
Route::middleware('auth:api')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);

    // Telegram preferences for the authenticated user (chat id + enable flag).
    Route::get('me/telegram', [TelegramSettingsController::class, 'show']);
    Route::put('me/telegram', [TelegramSettingsController::class, 'update']);
    Route::post('me/telegram/test', [TelegramSettingsController::class, 'test']);

    // Project planning and calendar tasks, all scoped to the authenticated user.
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('tasks', TaskController::class);
});
