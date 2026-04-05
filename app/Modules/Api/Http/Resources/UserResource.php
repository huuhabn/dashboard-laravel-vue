<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Resources;

use App\Modules\Api\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'phone' => $this->phone,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'has_password' => $this->password !== null,
            'two_factor_enabled' => $this->two_factor_confirmed_at !== null,
            'google_linked' => $this->google_id !== null && $this->google_id !== '',
            'github_linked' => $this->github_id !== null && $this->github_id !== '',
        ];
    }
}
