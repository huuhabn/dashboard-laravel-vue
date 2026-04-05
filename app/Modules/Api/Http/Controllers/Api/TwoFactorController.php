<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Api\Exceptions\InvalidPasswordException;
use App\Modules\Api\Exceptions\InvalidTwoFactorCodeException;
use App\Modules\Api\Exceptions\TwoFactorStateException;
use App\Modules\Api\Http\Requests\Api\ConfirmTwoFactorRequest;
use App\Modules\Api\Http\Requests\Api\DisableTwoFactorRequest;
use App\Modules\Api\Http\Resources\UserResource;
use App\Modules\Api\Services\TwoFactor\UserTwoFactorSetupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class TwoFactorController extends Controller
{
    public function store(
        Request $request,
        UserTwoFactorSetupService $twoFactor,
    ): JsonResponse {
        try {
            $payload = $twoFactor->beginEnable($request->user());
        } catch (TwoFactorStateException $e) {
            throw ValidationException::withMessages([
                'two_factor' => [$e->getMessage()],
            ]);
        }

        return response()->json([
            'data' => [
                'secret' => $payload['secret'],
                'otpauth_url' => $payload['otpauth_url'],
            ],
        ]);
    }

    public function confirm(
        ConfirmTwoFactorRequest $request,
        UserTwoFactorSetupService $twoFactor,
    ): JsonResponse {
        try {
            $recoveryCodes = $twoFactor->confirmEnable($request->user(), $request->validated('code'));
        } catch (InvalidTwoFactorCodeException $e) {
            throw ValidationException::withMessages([
                'code' => [$e->getMessage()],
            ]);
        } catch (TwoFactorStateException $e) {
            throw ValidationException::withMessages([
                'two_factor' => [$e->getMessage()],
            ]);
        }

        return response()->json([
            'data' => [
                'recovery_codes' => $recoveryCodes,
                'user' => new UserResource($request->user()->fresh()),
            ],
        ]);
    }

    public function destroy(
        DisableTwoFactorRequest $request,
        UserTwoFactorSetupService $twoFactor,
    ): JsonResponse {
        try {
            $twoFactor->disable(
                $request->user(),
                $request->validated('code'),
                $request->validated('current_password'),
            );
        } catch (InvalidPasswordException $e) {
            throw ValidationException::withMessages([
                'current_password' => [$e->getMessage()],
            ]);
        } catch (InvalidTwoFactorCodeException $e) {
            throw ValidationException::withMessages([
                'code' => [$e->getMessage()],
            ]);
        } catch (TwoFactorStateException $e) {
            throw ValidationException::withMessages([
                'two_factor' => [$e->getMessage()],
            ]);
        }

        return response()->json([
            'message' => 'Two-factor authentication disabled.',
            'user' => new UserResource($request->user()->fresh()),
        ]);
    }
}
