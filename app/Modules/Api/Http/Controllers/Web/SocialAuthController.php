<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Api\Enums\SocialProvider;
use App\Modules\Api\Services\Social\SocialLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SocialAuthController extends Controller
{
    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $social = $this->resolveProvider($provider);

        $intent = $request->query('intent');
        $request->session()->put(
            'social_auth_intent',
            $intent === 'register' ? 'register' : 'login',
        );

        return Socialite::driver($social->value)->redirect();
    }

    public function callback(
        Request $request,
        string $provider,
        SocialLoginService $social,
    ): RedirectResponse {
        $socialProvider = $this->resolveProvider($provider);

        try {
            $socialUser = Socialite::driver($socialProvider->value)->user();
        } catch (\Throwable) {
            return redirect('/login?social_error=1');
        }

        try {
            $user = $social->findOrCreateFromOAuth($socialProvider, $socialUser);
        } catch (\InvalidArgumentException) {
            return redirect('/login?social_error=2');
        }

        $token = $social->createExchangeKey($user);

        $intent = $request->session()->pull('social_auth_intent', 'login');
        $path = $intent === 'register' ? '/register' : '/login';

        return redirect($path.'?social_exchange='.$token);
    }

    private function resolveProvider(string $provider): SocialProvider
    {
        $social = SocialProvider::tryFromRoute($provider);

        if ($social === null || ! $social->isConfigured()) {
            throw new NotFoundHttpException;
        }

        return $social;
    }
}
