<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\LogsExportController;
use App\Http\Controllers\ServiceController;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\Middleware\Authenticate as AuthenticateMiddleware;


Route::get('/campuses', fn() => Campus::orderBy('name')->get());
Route::get('/campuses/{campus}/services', [ServiceController::class, 'index']);

Route::get('/logs/export', [LogsExportController::class, 'exportCsv'])
    ->name('logs.export')
    ->withoutMiddleware([AuthenticateMiddleware::class]);

Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/signin', [AuthController::class, 'signin'])->middleware('throttle:5,1');
// Other routes...
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/logs', [LogsController::class, 'index']);
    Route::get('/logs/{id}', [LogsController::class, 'show']);
    Route::post('/logs', [LogsController::class, 'store']);
    Route::post('/logs/batch', [LogsController::class, 'batch']);
    Route::delete('/logs', [LogsController::class, 'destroyByDate'])
        ->middleware('auth:sanctum');

    Route::post('/signout', [AuthController::class, 'signout']);
});

Route::middleware('auth:sanctum')->get('/me', function (Request $r) {
    return $r->user()->load('campus'); // important
});

Route::middleware(['auth:sanctum', EnsureUserIsAdmin::class])
    ->prefix('admin')
    ->group(function () {
        Route::apiResource('users', AdminUserController::class)
            ->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole']);
    });
