<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Api\Http\Requests\Api\LoginRequest;
use App\Modules\Api\Services\Authentication\AuthTokenResponseService;
use App\Modules\Api\Services\Authentication\PersonalAccessTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class TokenController extends Controller
{
    public function store(
        LoginRequest $request,
        PersonalAccessTokenService $tokens,
        AuthTokenResponseService $authResponse,
    ): JsonResponse {
        $user = $tokens->validatePasswordCredentials(
            $request->validated('email'),
            $request->validated('password'),
        );

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        return $authResponse->issueOrChallenge(
            $user,
            $request->input('device_name', 'spa'),
        );
    }

    public function destroy(
        Request $request,
        PersonalAccessTokenService $tokens,
    ): JsonResponse {
        $tokens->revokeCurrent($request->user());

        return response()->json(['message' => 'Token revoked.']);
    }
}
