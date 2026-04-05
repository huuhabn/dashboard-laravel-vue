<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Api\Http\Requests\Api\SocialExchangeRequest;
use App\Modules\Api\Services\Authentication\AuthTokenResponseService;
use App\Modules\Api\Services\Social\SocialLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

final class SocialExchangeController extends Controller
{
    public function store(
        SocialExchangeRequest $request,
        SocialLoginService $social,
        AuthTokenResponseService $authResponse,
    ): JsonResponse {
        $user = $social->findUserByExchangeToken($request->validated('exchange_token'));

        if ($user === null) {
            throw ValidationException::withMessages([
                'exchange_token' => [__('This login link is invalid or has expired.')],
            ]);
        }

        return $authResponse->issueOrChallenge(
            $user,
            $request->input('device_name', 'spa'),
        );
    }
}
