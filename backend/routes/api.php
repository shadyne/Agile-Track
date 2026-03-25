<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RecentController;
use App\Http\Controllers\EpicController;
use App\Http\Controllers\EpicDetailController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\BacklogController;
use App\Http\Controllers\ItemDetailController;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/dashboard', [DashboardController::class, 'global']);
    Route::apiResource('spaces', SpaceController::class)
        ->except(['show']);

    Route::get('/spaces/{spaceId}/boards', [BoardController::class, 'index']);
    Route::post('/spaces/{spaceId}/boards', [BoardController::class, 'store']);
    Route::put('/spaces/{spaceId}/boards/{boardId}', [BoardController::class, 'update']);
    Route::delete('/spaces/{spaceId}/boards/{boardId}', [BoardController::class, 'destroy']);

    Route::get('/boards/{boardId}', [BoardController::class, 'show']);

    // Epics
    Route::get('/boards/{boardId}/epics', [EpicController::class, 'index']);
    Route::post('/boards/{boardId}/epics', [EpicController::class, 'store']);
    Route::get('/boards/{boardId}/epics/labels', [EpicController::class, 'getLabels']);
    Route::get('/boards/{boardId}/epics/{epicId}', [EpicDetailController::class, 'show']);
    Route::put('/boards/{boardId}/epics/{epicId}', [EpicDetailController::class, 'update']);
    Route::delete('/boards/{boardId}/epics/{epicId}', [EpicController::class, 'destroy']);
    Route::post('/boards/{boardId}/epics/{epicId}/items', [EpicController::class, 'storeItem']);
    Route::get('/boards/{boardId}/epics/{epicId}/history', [EpicDetailController::class, 'history']);
    Route::post('/boards/{boardId}/epics/{epicId}/comments', [EpicDetailController::class, 'storeComment']);
    Route::delete('/boards/{boardId}/epics/{epicId}/comments/{commentId}', [EpicDetailController::class, 'deleteComment']);

    // Sprints
    Route::get('/boards/{boardId}/sprints', [SprintController::class, 'index']);
    Route::post('/boards/{boardId}/sprints', [SprintController::class, 'store']);
    Route::post('/boards/{boardId}/sprints/{sprintId}/start', [SprintController::class, 'start']);
    Route::post('/boards/{boardId}/sprints/{sprintId}/complete', [SprintController::class, 'complete']);
    Route::delete('/boards/{boardId}/sprints/{sprintId}', [SprintController::class, 'destroy']);
    Route::patch('/boards/{boardId}/sprints/{sprintId}/rename', [SprintController::class, 'rename']);

    // Backlog
    Route::get('/boards/{boardId}/backlog', [BacklogController::class, 'index']);
    Route::post('/boards/{boardId}/backlog/items', [BacklogController::class, 'store']);
    Route::patch('/boards/{boardId}/backlog/items/{itemId}/move-to-sprint', [BacklogController::class, 'moveToSprint']);
    Route::patch('/boards/{boardId}/backlog/items/{itemId}/status', [BacklogController::class, 'updateStatus']);
    
    // Item Detail (EpicItem)
    Route::get('/boards/{boardId}/items/{itemId}', [ItemDetailController::class, 'show']);
    Route::put('/boards/{boardId}/items/{itemId}', [ItemDetailController::class, 'update']);
    Route::get('/boards/{boardId}/items/{itemId}/history', [ItemDetailController::class, 'history']);

    // Dashboard
    Route::get('/spaces/{spaceId}/dashboard', [DashboardController::class, 'index']);
    Route::get('/spaces/{spaceId}/recent', [RecentController::class, 'index']);
});