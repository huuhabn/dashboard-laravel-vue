<?php

declare(strict_types=1);

namespace App\Modules\Api\Enums;

enum OtpDeliverTo: string
{
    case Email = 'email';
    case Phone = 'phone';
    case Both = 'both';

    public function allowsEmail(): bool
    {
        return $this === self::Email || $this === self::Both;
    }

    public function allowsPhone(): bool
    {
        return $this === self::Phone || $this === self::Both;
    }
}
