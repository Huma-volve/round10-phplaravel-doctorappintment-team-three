<?php

use App\Http\Controllers\API\AdminNotificationController;
use App\Http\Controllers\API\AppointmentController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\DoctorController;
use App\Http\Controllers\API\DoctorSearchController;
use App\Http\Controllers\API\FavoriteController;
use App\Http\Controllers\API\GoogleAuthController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ReviewController;
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
    Route::get('/doctors/{doctor}/reviews', [ReviewController::class, 'indexForDoctor']);
    Route::get('/doctors/nearby', [DoctorController::class, 'nearby']);
    Route::get('/doctors/search', [DoctorSearchController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::delete('/favorites/{favorite}', [FavoriteController::class, 'destroy']);

        Route::post('/doctors/{doctor}/favorite', [DoctorController::class, 'favorite']);
        Route::delete('/doctors/{doctor}/favorite', [DoctorController::class, 'unfavorite']);

        Route::post('/clinics/{clinic}/favorite', [FavoriteController::class, 'favoriteClinic']);
        Route::delete('/clinics/{clinic}/favorite', [FavoriteController::class, 'unfavoriteClinic']);

        Route::get('/doctors/search/history', [DoctorSearchController::class, 'history']);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{inAppNotification}/read', [NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

        Route::post('/admin/notifications/broadcast', [AdminNotificationController::class, 'broadcast'])
            ->middleware('role:admin');

        Route::post('/appointments', [AppointmentController::class, 'store']);
        Route::patch('/appointments/{appointment}', [AppointmentController::class, 'update']);

        Route::post('/appointments/{appointment}/review', [ReviewController::class, 'storeReview']);
        Route::post('/appointments/{appointment}/feedback', [ReviewController::class, 'storeSessionFeedback']);

        Route::get('/chats', [ChatController::class, 'index']);
        Route::post('/chats', [ChatController::class, 'store']);
        Route::get('/chats/{chat}', [ChatController::class, 'show']);
        Route::get('/chats/{chat}/messages', [ChatController::class, 'messages']);
        Route::post('/chats/{chat}/messages', [ChatController::class, 'sendMessage']);
        Route::post('/chats/{chat}/read', [ChatController::class, 'markRead']);
        Route::post('/chats/{chat}/favorite', [ChatController::class, 'favorite']);
        Route::delete('/chats/{chat}/favorite', [ChatController::class, 'unfavorite']);
        Route::post('/chats/{chat}/archive', [ChatController::class, 'archive']);
        Route::delete('/chats/{chat}/archive', [ChatController::class, 'unarchive']);

        Route::patch('/payments/{payment}', [PaymentController::class, 'update']);
    });
});
