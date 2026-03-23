<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\SpaceController;



// Auth routes (public)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    Route::apiResource('spaces', SpaceController::class)
        ->except(['show']);

    Route::get('/spaces/{spaceId}/boards', [BoardController::class, 'index']);
    Route::post('/spaces/{spaceId}/boards', [BoardController::class, 'store']);
    Route::delete('/spaces/{spaceId}/boards/{boardId}', [BoardController::class, 'destroy']);

    Route::get('/spaces/{spaceId}/dashboard', [DashboardController::class, 'index']);
    Route::get('/spaces/{spaceId}/recent', [RecentController::class, 'index']);
});