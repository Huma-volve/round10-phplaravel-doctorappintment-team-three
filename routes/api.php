<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DoctorController;
use App\Http\Controllers\API\GoogleAuthController;
use App\Http\Controllers\API\DoctorSearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/google', GoogleAuthController::class);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::delete('/account', [AuthController::class, 'deleteAccount']);
        });
    });

    Route::get('/doctors/{doctor}', [DoctorController::class, 'show']);
    Route::get('/doctors/nearby', [DoctorController::class, 'nearby']);
    Route::get('/doctors/search', [DoctorSearchController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/doctors/{doctor}/favorite', [DoctorController::class, 'favorite']);
        Route::delete('/doctors/{doctor}/favorite', [DoctorController::class, 'unfavorite']);
        Route::get('/doctors/search/history', [DoctorSearchController::class, 'history']);
    });
});

