<?php

use App\Modules\Api\Http\Controllers\Api\AccountController;
use App\Modules\Api\Http\Controllers\Api\Auth\AuthConfigController;
use App\Modules\Api\Http\Controllers\Api\Auth\EmailVerificationNotificationController;
use App\Modules\Api\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Modules\Api\Http\Controllers\Api\Auth\LoginOtpController;
use App\Modules\Api\Http\Controllers\Api\Auth\RegisterController;
use App\Modules\Api\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Modules\Api\Http\Controllers\Api\Auth\SocialExchangeController;
use App\Modules\Api\Http\Controllers\Api\Auth\SocialProvidersController;
use App\Modules\Api\Http\Controllers\Api\Auth\TokenController;
use App\Modules\Api\Http\Controllers\Api\Auth\TwoFactorLoginController;
use App\Modules\Api\Http\Controllers\Api\DashboardController;
use App\Modules\Api\Http\Controllers\Api\PasswordController;
use App\Modules\Api\Http\Controllers\Api\SocialAccountController;
use App\Modules\Api\Http\Controllers\Api\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::get('auth/social/providers', SocialProvidersController::class);
Route::get('auth/config', AuthConfigController::class);

Route::post('auth/register', [RegisterController::class, 'store'])
    ->middleware('throttle:10,1');

Route::post('auth/token', [TokenController::class, 'store'])
    ->middleware('throttle:10,1');

Route::post('auth/otp/request', [LoginOtpController::class, 'request'])
    ->middleware('throttle:6,1');

Route::post('auth/otp/verify', [LoginOtpController::class, 'verify'])
    ->middleware('throttle:12,1');

Route::post('auth/token/two-factor', [TwoFactorLoginController::class, 'store'])
    ->middleware('throttle:10,1');

Route::post('auth/social/exchange', [SocialExchangeController::class, 'store'])
    ->middleware('throttle:10,1');

Route::post('auth/forgot-password', [ForgotPasswordController::class, 'store'])
    ->middleware('throttle:6,1');

Route::post('auth/reset-password', [ResetPasswordController::class, 'store'])
    ->middleware('throttle:6,1');

Route::delete('auth/token', [TokenController::class, 'destroy'])
    ->middleware('auth:sanctum');

Route::post('auth/email/resend', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth:sanctum', 'throttle:6,1']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('me', [AccountController::class, 'show']);
    Route::post('me/avatar', [AccountController::class, 'uploadAvatar'])
        ->middleware('throttle:20,1');
    Route::patch('me', [AccountController::class, 'update']);
    Route::put('me/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1');

    Route::post('me/two-factor', [TwoFactorController::class, 'store'])
        ->middleware('throttle:6,1');
    Route::post('me/two-factor/confirm', [TwoFactorController::class, 'confirm'])
        ->middleware('throttle:12,1');
    Route::delete('me/two-factor', [TwoFactorController::class, 'destroy'])
        ->middleware('throttle:6,1');

    Route::delete('me/social/{provider}', [SocialAccountController::class, 'destroy'])
        ->middleware('throttle:12,1');
});

Route::middleware(['auth:sanctum', 'verified'])->group(function (): void {
    Route::delete('me', [AccountController::class, 'destroy']);
    Route::get('dashboard', [DashboardController::class, 'show']);
});
