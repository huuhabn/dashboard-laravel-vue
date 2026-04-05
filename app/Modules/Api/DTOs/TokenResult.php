<?php

declare(strict_types=1);

namespace App\Modules\Api\DTOs;

use App\Modules\Api\Models\User;

/**
 * Result of issuing a personal access token. Replaces the untyped
 * array{user: User, plain_text_token: string} pattern.
 */
readonly class TokenResult
{
    public function __construct(
        public User $user,
        public string $plainTextToken,
    ) {}
}
