<?php

declare(strict_types=1);

namespace App\Modules\Api\DTOs;

use App\Modules\Api\Enums\OtpOutcomeKind;
use Carbon\CarbonInterface;

/**
 * Result of requesting a login OTP code. Replaces the untyped
 * array{kind, resend_available_at, retry_after_seconds?, cooldown_error_field?} pattern.
 */
readonly class OtpOutcome
{
    public function __construct(
        public OtpOutcomeKind $kind,
        public ?CarbonInterface $resendAvailableAt = null,
        public ?int $retryAfterSeconds = null,
        public ?string $cooldownErrorField = null,
    ) {}

    public static function sent(CarbonInterface $resendAvailableAt): self
    {
        return new self(
            kind: OtpOutcomeKind::Sent,
            resendAvailableAt: $resendAvailableAt,
        );
    }

    public static function silent(): self
    {
        return new self(kind: OtpOutcomeKind::Silent);
    }

    public static function cooldown(int $retryAfterSeconds, string $errorField): self
    {
        return new self(
            kind: OtpOutcomeKind::Cooldown,
            retryAfterSeconds: $retryAfterSeconds,
            cooldownErrorField: $errorField,
        );
    }

    public function isCooldown(): bool
    {
        return $this->kind === OtpOutcomeKind::Cooldown;
    }
}
