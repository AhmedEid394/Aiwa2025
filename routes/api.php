<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ServiceProviderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// User routes
Route::post('/users/register', [UserController::class, 'register']);
Route::post('/users/login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users/logout', [UserController::class, 'logout']);
    Route::get('/users/profile', [UserController::class, 'show']);
    Route::put('/users/profile', [UserController::class, 'update']);
    Route::delete('/users/profile', [UserController::class, 'destroy']);
});

// Service Provider routes
Route::post('/providers/register', [ServiceProviderController::class, 'register']);
Route::post('/providers/login', [ServiceProviderController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/providers/logout', [ServiceProviderController::class, 'logout']);
    Route::get('/providers/profile', [ServiceProviderController::class, 'show']);
    Route::put('/providers/profile', [ServiceProviderController::class, 'update']);
    Route::delete('/providers/profile', [ServiceProviderController::class, 'destroy']);
});



