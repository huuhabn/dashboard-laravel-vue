<?php

declare(strict_types=1);

namespace App\Modules\Api\Exceptions;

/**
 * 2FA is already enabled, not enabled, or not in expected state.
 */
final class TwoFactorStateException extends \RuntimeException
{
    public function __construct(string $message = 'Two-factor authentication is in an unexpected state.')
    {
        parent::__construct($message);
    }
}
