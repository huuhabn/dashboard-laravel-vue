<?php

declare(strict_types=1);

namespace App\Modules\Api\Exceptions;

/**
 * The TOTP code or recovery code provided was invalid.
 */
final class InvalidTwoFactorCodeException extends \InvalidArgumentException
{
    public function __construct(string $message = 'Invalid authentication code.')
    {
        parent::__construct($message);
    }
}
