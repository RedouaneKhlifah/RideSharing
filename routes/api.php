<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RideController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware(['api', 'SetLocale'])->group(function () {
    
    Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail']);

    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('/sign-up', 'signUp');
        Route::post('/sign-in', 'signIn');
        Route::post('/refresh-token', 'refreshToken');
        Route::post('/logout', 'logout');
    });

    // Protected routes requiring JWT authentication
    Route::middleware('auth:api')->group(function () {
        Route::post('/auth/resend-verification', [AuthController::class, 'resendVerificationCode']);

        // Get authenticated user
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // Update user profile
        Route::post('/user/profile', [UserController::class, 'updateProfile']);

        // Verify token
        Route::get('/verify-token', function (Request $request) {
            return response()->json(['message' => 'Token is valid']);
        });


        // User profile routes
        Route::prefix('rides')->controller(RideController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/filter', 'filter');
            Route::get('/my-rides', 'myRides');
            Route::get('/{ride}', 'show');
            Route::put('/{ride}', 'update');
            Route::patch('/{ride}/archive', 'archive');
            Route::delete('/{ride}', 'destroy');

        });
    
     
    });
});