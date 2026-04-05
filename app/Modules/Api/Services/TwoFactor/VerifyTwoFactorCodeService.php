<?php

declare(strict_types=1);

namespace App\Modules\Api\Services\TwoFactor;

use App\Modules\Api\Models\User;
use App\Modules\Api\Repositories\Contracts\UserRepositoryContract;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

final class VerifyTwoFactorCodeService
{
    public function __construct(
        private readonly UserRepositoryContract $users,
        private readonly Google2FA $google2fa,
    ) {}

    /**
     * Returns true if the code was a valid TOTP or a one-time recovery code (consumed on success).
     */
    public function verify(User $user, string $code): bool
    {
        $normalized = trim(str_replace(' ', '', $code));

        if ($normalized === '') {
            return false;
        }

        $secret = $user->two_factor_secret;

        if ($secret !== null && $secret !== '' && $this->google2fa->verifyKey($secret, $normalized)) {
            return true;
        }

        return $this->tryConsumeRecoveryCode($user, $normalized);
    }

    private function tryConsumeRecoveryCode(User $user, string $code): bool
    {
        /** @var array<int, string>|null $hashed */
        $hashed = $user->two_factor_recovery_codes;

        if (! is_array($hashed) || $hashed === []) {
            return false;
        }

        $remaining = [];
        $matched = false;

        foreach ($hashed as $stored) {
            if (! $matched && Hash::check($code, $stored)) {
                $matched = true;

                continue;
            }

            $remaining[] = $stored;
        }

        if (! $matched) {
            return false;
        }

        $user->two_factor_recovery_codes = $remaining;
        $this->users->save($user);

        return true;
    }
}
