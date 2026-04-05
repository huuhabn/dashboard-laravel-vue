<?php

declare(strict_types=1);

namespace App\Modules\Api\Enums;

/**
 * Supported OAuth providers. Centralises provider-specific column names,
 * redirect URLs, scopes, and client credential keys so that controllers
 * and services never branch on raw strings.
 */
enum SocialProvider: string
{
    case Google = 'google';
    case GitHub = 'github';

    /**
     * The User model column that stores the provider's external user ID.
     */
    public function idColumn(): string
    {
        return match ($this) {
            self::Google => 'google_id',
            self::GitHub => 'github_id',
        };
    }

    /**
     * OAuth redirect URL (used by Socialite).
     * Falls back to `config('services.{provider}.redirect')`.
     */
    public function redirectUrl(): string
    {
        return (string) config("services.{$this->value}.redirect");
    }

    /**
     * OAuth scopes requested during authentication.
     */
    public function scopes(): array
    {
        return match ($this) {
            self::Google => ['openid', 'profile', 'email'],
            self::GitHub => ['user:email'],
        };
    }

    /**
     * Whether the provider is configured (client_id is present).
     */
    public function isConfigured(): bool
    {
        return filled(config("services.{$this->value}.client_id"));
    }

    /**
     * Build the availability map for all providers.
     *
     * @return array<string, bool>
     */
    public static function availabilityMap(): array
    {
        $map = [];

        foreach (self::cases() as $provider) {
            $map[$provider->value] = $provider->isConfigured();
        }

        return $map;
    }

    /**
     * Resolve from a route parameter string; returns null for unsupported values.
     */
    public static function tryFromRoute(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
