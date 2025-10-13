<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\ServiceController;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/campuses', fn() => Campus::orderBy('name')->get());
Route::get('/campuses/{campus}/services', [ServiceController::class, 'index']);


Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/signin', [AuthController::class, 'signin'])->middleware('throttle:5,1');
// Other routes...
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/logs', [LogsController::class, 'index']);
    Route::get('/logs/{id}', [LogsController::class, 'show']);
    Route::post('/logs', [LogsController::class, 'store']);
    Route::delete('/logs', [LogsController::class, 'destroyByDate'])
        ->middleware('auth:sanctum');


    Route::get('/user', fn(Request $request) => $request->user()->load('campus'));

    Route::post('/signout', [AuthController::class, 'signout']);
});
