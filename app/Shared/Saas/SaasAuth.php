<?php

declare(strict_types=1);

namespace App\Shared\Saas;

use App\Modules\Api\Enums\LoginMethod;
use App\Modules\Api\Enums\OtpDeliverTo;

/**
 * Reads SaaS auth toggles from config/services.php (services.saas) for API and UI.
 */
final class SaasAuth
{
    public static function loginMethod(): LoginMethod
    {
        return LoginMethod::tryFrom(
            (string) config('services.saas.auth.login_methods', 'both')
        ) ?? LoginMethod::Both;
    }

    public static function allowsPasswordLogin(): bool
    {
        return self::loginMethod()->allowsPassword();
    }

    public static function allowsOtpLogin(): bool
    {
        return self::loginMethod()->allowsOtp();
    }

    public static function otpDeliverTo(): OtpDeliverTo
    {
        return OtpDeliverTo::tryFrom(
            (string) config('services.saas.auth.otp_deliver_to', 'email')
        ) ?? OtpDeliverTo::Email;
    }

    public static function otpAllowsEmail(): bool
    {
        return self::otpDeliverTo()->allowsEmail();
    }

    public static function otpAllowsPhone(): bool
    {
        return self::otpDeliverTo()->allowsPhone();
    }

    public static function defaultPhoneRegion(): string
    {
        $r = (string) config('services.saas.auth.default_phone_region', 'VN');

        return $r !== '' ? strtoupper($r) : 'VN';
    }

    /**
     * @return array{login_methods: string, otp_deliver_to: string, default_phone_region: string}
     */
    public static function publicAuthPayload(): array
    {
        return [
            'login_methods' => self::loginMethod()->value,
            'otp_deliver_to' => self::otpDeliverTo()->value,
            'default_phone_region' => self::defaultPhoneRegion(),
        ];
    }
}
