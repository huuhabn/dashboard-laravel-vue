<?php

declare(strict_types=1);

namespace App\Modules\Api\Services\Authentication;

use App\Modules\Api\Contracts\LoginOtpSmsSenderContract;
use Illuminate\Support\Facades\Log;

/**
 * Development-friendly SMS stub: logs the OTP (never use in production).
 */
final class LogLoginOtpSmsSender implements LoginOtpSmsSenderContract
{
    public function sendLoginOtp(string $e164Phone, string $code, string $recipientName): void
    {
        Log::info('Login OTP SMS (log driver)', [
            'to' => $e164Phone,
            'recipient' => $recipientName,
            'code' => $code,
        ]);
    }
}
