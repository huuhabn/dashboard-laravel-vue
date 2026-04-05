<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Api\Http\Requests\Api\RequestLoginOtpRequest;
use App\Modules\Api\Http\Requests\Api\VerifyLoginOtpRequest;
use App\Modules\Api\Services\Authentication\AuthTokenResponseService;
use App\Modules\Api\Services\Authentication\LoginOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

final class LoginOtpController extends Controller
{
    public function request(
        RequestLoginOtpRequest $request,
        LoginOtpService $service,
    ): JsonResponse {
        $validated = $request->validated();
        $email = $validated['email'] ?? null;
        $phone = $validated['phone'] ?? null;

        $outcome = $service->requestCode(
            is_string($email) ? $email : null,
            is_string($phone) ? $phone : null,
        );

        if ($outcome->isCooldown()) {
            $field = $outcome->cooldownErrorField ?? 'email';

            return response()->json([
                'message' => __('Please wait before requesting another sign-in code.'),
                'retry_after_seconds' => $outcome->retryAfterSeconds,
                'errors' => [
                    $field => [__('Please wait a minute before requesting another sign-in code.')],
                ],
            ], 422);
        }

        return response()->json([
            'message' => __('If an account exists, a sign-in code was sent.'),
            'resend_available_at' => $outcome->resendAvailableAt?->toIso8601String(),
        ]);
    }

    public function verify(
        VerifyLoginOtpRequest $request,
        LoginOtpService $loginOtp,
        AuthTokenResponseService $authResponse,
    ): JsonResponse {
        $validated = $request->validated();
        $email = $validated['email'] ?? null;
        $phone = $validated['phone'] ?? null;
        $code = $validated['code'];

        $user = $loginOtp->verifyCode(
            is_string($email) ? $email : null,
            is_string($phone) ? $phone : null,
            $code,
        );

        if ($user === null) {
            throw ValidationException::withMessages([
                'code' => [__('Invalid or expired sign-in code.')],
            ]);
        }

        return $authResponse->issueOrChallenge(
            $user,
            $request->input('device_name', 'spa'),
        );
    }
}
