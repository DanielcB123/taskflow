<?php
//routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskController;

// All API calls require API key authentication
Route::middleware('apikey')->group(function () {
    Route::get('tasks', [TaskController::class, 'index']);
    Route::post('tasks', [TaskController::class, 'store']);
    Route::match(['put', 'patch'], 'tasks/{task}', [TaskController::class, 'update']);
    Route::delete('tasks/{task}', [TaskController::class, 'destroy']);
    Route::patch('tasks/{task}/move', [TaskController::class, 'move']);
    Route::post('tasks/{task}/assign', [TaskController::class, 'assign']);
});
