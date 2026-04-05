<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Api\Http\Requests\Api\TwoFactorLoginRequest;
use App\Modules\Api\Services\Authentication\AuthTokenResponseService;
use App\Modules\Api\Services\Authentication\TwoFactorLoginChallengeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

final class TwoFactorLoginController extends Controller
{
    public function store(
        TwoFactorLoginRequest $request,
        TwoFactorLoginChallengeService $challenge,
    ): JsonResponse {
        $result = $challenge->completePendingLogin(
            $request->validated('pending_token'),
            $request->validated('code'),
            $request->input('device_name', 'spa'),
        );

        if ($result === null) {
            throw ValidationException::withMessages([
                'code' => [__('Invalid authentication code or session expired.')],
            ]);
        }

        return AuthTokenResponseService::tokenResultToJson($result);
    }
}
