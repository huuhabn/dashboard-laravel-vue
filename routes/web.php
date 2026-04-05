<?php

use App\Modules\Api\Http\Controllers\Web\SocialAuthController;
use App\Modules\Api\Http\Controllers\Web\VerifyEmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->whereIn('provider', ['google', 'github']);

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->whereIn('provider', ['google', 'github']);

Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::get('/reset-password/{token}', function (Request $request, string $token) {
    $email = (string) $request->query('email', '');
    $query = http_build_query([
        'token' => $token,
        'email' => $email,
    ]);

    return redirect('/reset-password?'.$query);
})->name('password.reset');

/*
 * Do not send real static assets through the SPA: without this, /storage/* or /build/*
 * can match here and return HTML, which breaks <img src="/storage/..."> and asset requests
 * when the web stack forwards those paths to Laravel.
 */
Route::view('/{any?}', 'app')->where('any', '^(?!storage/|build/).*');
