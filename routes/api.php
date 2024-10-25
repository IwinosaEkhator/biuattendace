<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LogsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/signin', [AuthController::class, 'signin']);
Route::post('/signout', [AuthController::class, 'signout']);

Route::post('/logs', [LogsController::class, 'store']);
Route::get('/logs', [LogsController::class, 'index']);
