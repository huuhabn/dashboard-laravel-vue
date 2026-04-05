<?php

declare(strict_types=1);

namespace App\Modules\Api\Enums;

enum LoginMethod: string
{
    case PasswordOnly = 'password_only';
    case OtpOnly = 'otp_only';
    case Both = 'both';

    public function allowsPassword(): bool
    {
        return match ($this) {
            self::PasswordOnly, self::Both => true,
            self::OtpOnly => false,
        };
    }

    public function allowsOtp(): bool
    {
        return match ($this) {
            self::OtpOnly, self::Both => true,
            self::PasswordOnly => false,
        };
    }
}
