<?php

declare(strict_types=1);

namespace App\Modules\Api\Services\Authentication;

use App\Modules\Api\DTOs\TokenResult;
use App\Modules\Api\Models\User;
use App\Modules\Api\Repositories\Contracts\UserRepositoryContract;
use App\Modules\Api\Services\TwoFactor\VerifyTwoFactorCodeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Pending 2FA after password / OAuth / OTP, then complete with TOTP or recovery code.
 */
final class TwoFactorLoginChallengeService
{
    private const CACHE_PREFIX = 'login_2fa_pending:';

    private const TTL_SECONDS = 300;

    public function __construct(
        private readonly UserRepositoryContract $users,
        private readonly VerifyTwoFactorCodeService $verifyTwoFactor,
        private readonly PersonalAccessTokenService $tokens,
    ) {}

    public function createPendingToken(User $user): string
    {
        $token = Str::random(64);
        Cache::put(self::CACHE_PREFIX.$token, $user->id, now()->addSeconds(self::TTL_SECONDS));

        return $token;
    }

    public function completePendingLogin(string $pendingToken, string $code, string $deviceName): ?TokenResult
    {
        $userId = $this->getUserId($pendingToken);

        if ($userId === null) {
            return null;
        }

        $user = $this->users->findById($userId);

        if ($user === null || ! $user->hasTwoFactorEnabled()) {
            return null;
        }

        if (! $this->verifyTwoFactor->verify($user, $code)) {
            return null;
        }

        $this->forget($pendingToken);

        return $this->tokens->issueForUser($user, $deviceName);
    }

    public function getUserId(string $pendingToken): ?int
    {
        $userId = Cache::get(self::CACHE_PREFIX.$pendingToken);

        return is_int($userId) ? $userId : (is_numeric($userId) ? (int) $userId : null);
    }

    public function forget(string $pendingToken): void
    {
        Cache::forget(self::CACHE_PREFIX.$pendingToken);
    }
}
