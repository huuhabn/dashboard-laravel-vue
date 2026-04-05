<?php

declare(strict_types=1);

namespace App\Modules\Api\Services\Authentication;

use App\Modules\Api\DTOs\TokenResult;
use App\Modules\Api\Http\Resources\UserResource;
use App\Modules\Api\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Centralises the "check 2FA → issue token OR return pending challenge" pattern
 * used across password login, OTP login, and social exchange controllers.
 */
final class AuthTokenResponseService
{
    public function __construct(
        private readonly PersonalAccessTokenService $tokens,
        private readonly TwoFactorLoginChallengeService $twoFactorChallenge,
    ) {}

    /**
     * If the user has 2FA enabled, return a pending-challenge response.
     * Otherwise, issue a personal access token and return a token response.
     */
    public function issueOrChallenge(User $user, string $deviceName): JsonResponse
    {
        if ($user->hasTwoFactorEnabled()) {
            $pending = $this->twoFactorChallenge->createPendingToken($user);

            return response()->json([
                'two_factor_required' => true,
                'pending_token' => $pending,
            ]);
        }

        return $this->issueTokenResponse($user, $deviceName);
    }

    /**
     * Issue a token and return the standard token response.
     */
    public function issueTokenResponse(User $user, string $deviceName): JsonResponse
    {
        $result = $this->tokens->issueForUser($user, $deviceName);

        return self::tokenResultToJson($result);
    }

    public static function tokenResultToJson(TokenResult $result): JsonResponse
    {
        return response()->json([
            'token' => $result->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($result->user),
        ]);
    }
}
