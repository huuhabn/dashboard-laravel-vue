<?php

declare(strict_types=1);

namespace App\Modules\Api\Contracts;

/**
 * Sends login OTP via SMS. Replace binding with Twilio/Vonage in production.
 */
interface LoginOtpSmsSenderContract
{
    public function sendLoginOtp(string $e164Phone, string $code, string $recipientName): void;
}
