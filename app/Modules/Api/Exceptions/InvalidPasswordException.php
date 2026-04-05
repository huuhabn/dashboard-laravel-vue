<?php

declare(strict_types=1);

namespace App\Modules\Api\Exceptions;

/**
 * The current password provided was incorrect.
 */
final class InvalidPasswordException extends \InvalidArgumentException
{
    public function __construct(string $message = 'Current password is incorrect.')
    {
        parent::__construct($message);
    }
}
