<?php

declare(strict_types=1);

namespace App\Modules\Api\Enums;

enum OtpOutcomeKind: string
{
    case Sent = 'sent';
    case Silent = 'silent';
    case Cooldown = 'cooldown';
}
