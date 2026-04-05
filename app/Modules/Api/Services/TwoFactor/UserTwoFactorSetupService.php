<?php

declare(strict_types=1);

namespace App\Modules\Api\Services\TwoFactor;

use App\Modules\Api\Events\TwoFactorAuthenticationConfirmed;
use App\Modules\Api\Events\TwoFactorAuthenticationDisabled;
use App\Modules\Api\Exceptions\InvalidPasswordException;
use App\Modules\Api\Exceptions\InvalidTwoFactorCodeException;
use App\Modules\Api\Exceptions\TwoFactorStateException;
use App\Modules\Api\Models\User;
use App\Modules\Api\Repositories\Contracts\UserRepositoryContract;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Enable / confirm / disable TOTP 2FA for an authenticated user.
 */
final class UserTwoFactorSetupService
{
    public function __construct(
        private readonly UserRepositoryContract $users,
        private readonly Google2FA $google2fa,
        private readonly VerifyTwoFactorCodeService $verifyTwoFactor,
    ) {}

    /**
     * @return array{secret: string, otpauth_url: string}
     */
    public function beginEnable(User $user): array
    {
        if ($user->hasTwoFactorEnabled()) {
            throw new TwoFactorStateException('Two-factor authentication is already enabled.');
        }

        $secret = $this->google2fa->generateSecretKey();
        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $this->users->save($user);

        $otpauthUrl = $this->google2fa->getQRCodeUrl(config('app.name'), $user->email, $secret);

        return [
            'secret' => $secret,
            'otpauth_url' => $otpauthUrl,
        ];
    }

    /**
     * @return array<int, string> Plain recovery codes (shown once to the client).
     */
    public function confirmEnable(User $user, string $code): array
    {
        if ($user->two_factor_secret === null || $user->two_factor_secret === '') {
            throw new TwoFactorStateException('Start two-factor setup first.');
        }

        if ($user->hasTwoFactorEnabled()) {
            throw new TwoFactorStateException('Two-factor authentication is already enabled.');
        }

        if (! $this->google2fa->verifyKey($user->two_factor_secret, trim($code))) {
            throw new InvalidTwoFactorCodeException;
        }

        $plainCodes = [];
        $hashed = [];

        for ($i = 0; $i < 8; $i++) {
            $plain = Str::upper(Str::random(10));
            $plainCodes[] = $plain;
            $hashed[] = Hash::make($plain);
        }

        $user->two_factor_recovery_codes = $hashed;
        $user->two_factor_confirmed_at = now();
        $this->users->save($user);

        Event::dispatch(new TwoFactorAuthenticationConfirmed($user->id));

        return $plainCodes;
    }

    public function disable(User $user, string $code, ?string $currentPassword): void
    {
        if (! $user->hasTwoFactorEnabled()) {
            throw new TwoFactorStateException('Two-factor authentication is not enabled.');
        }

        if ($user->hasPassword()) {
            if ($currentPassword === null || $currentPassword === '') {
                throw new InvalidPasswordException('Current password is required.');
            }

            if (! Hash::check($currentPassword, $user->password)) {
                throw new InvalidPasswordException;
            }
        }

        if (! $this->verifyTwoFactor->verify($user, $code)) {
            throw new InvalidTwoFactorCodeException;
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $this->users->save($user);

        Event::dispatch(new TwoFactorAuthenticationDisabled($user->id));
    }
}
