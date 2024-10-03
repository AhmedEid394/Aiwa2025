<?php

use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// Public routes for users
Route::prefix('users')->group(function () {
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/login', [UserController::class, 'login']);
});

// Protected routes for authenticated users
Route::middleware('auth:sanctum')->prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']); // List all users (if needed)
    Route::get('/logout', [UserController::class, 'logout']);
    Route::get('/show', [UserController::class, 'show']);
    Route::put('/update', [UserController::class, 'update']);
    Route::delete('/destroy', [UserController::class, 'destroy']);
});

// Public routes for service providers
Route::prefix('providers')->group(function () {
    Route::post('/register', [ServiceProviderController::class, 'register']);
    Route::post('/login', [ServiceProviderController::class, 'login']);
});

// Protected routes for authenticated service providers
Route::middleware('auth:sanctum')->prefix('providers')->group(function () {
    Route::get('/', [ServiceProviderController::class, 'index']); // List all providers (if needed)
    Route::get('/logout', [ServiceProviderController::class, 'logout']);
    Route::get('/show', [ServiceProviderController::class, 'show']);
    Route::put('/update', [ServiceProviderController::class, 'update']);
    Route::delete('/destroy', [ServiceProviderController::class, 'destroy']);
});

