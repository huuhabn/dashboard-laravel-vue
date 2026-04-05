<?php

declare(strict_types=1);

namespace App\Modules\Api\Events;

final class TwoFactorAuthenticationDisabled
{
    public function __construct(
        public readonly int $userId,
    ) {}
}
