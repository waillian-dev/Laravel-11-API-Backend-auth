<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\AuthController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::apiResource('posts', PostController::class);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verifyOtp', [AuthController::class, 'verifyOtp']);
Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
Route::post('/verify-forget-otp', [AuthController::class, 'verifyForgetPasswordOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::middleware(['auth:sanctum'])->post('/reset-password', [AuthController::class, 'resetPassword']);

Route::post('/auth/google/mobile', [AuthController::class, 'loginWithGoogle']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

