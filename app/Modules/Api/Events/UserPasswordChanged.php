<?php

declare(strict_types=1);

namespace App\Modules\Api\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class UserPasswordChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
    ) {}
}
