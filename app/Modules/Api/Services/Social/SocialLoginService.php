<?php

declare(strict_types=1);

namespace App\Modules\Api\Services\Social;

use App\Modules\Api\Enums\SocialProvider;
use App\Modules\Api\Models\User;
use App\Modules\Api\Repositories\Contracts\UserRepositoryContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * OAuth user resolution, web callback exchange token, API unlink.
 */
final class SocialLoginService
{
    private const CACHE_PREFIX = 'social_exchange:';

    private const EXCHANGE_TTL_SECONDS = 300;

    public function __construct(
        private readonly UserRepositoryContract $users,
    ) {}

    public function findOrCreateFromOAuth(SocialProvider $provider, SocialiteUser $social): User
    {
        $email = $social->getEmail();

        if ($email === null || $email === '') {
            throw new \InvalidArgumentException('OAuth provider did not return an email address.');
        }

        $id = (string) $social->getId();
        $column = $provider->idColumn();

        $user = $this->users->findBySocialId($column, $id);

        if ($user !== null) {
            return $user;
        }

        $byEmail = $this->users->findByEmail($email);

        if ($byEmail !== null) {
            $byEmail->{$column} = $id;

            if ($byEmail->email_verified_at === null) {
                $byEmail->email_verified_at = now();
            }

            $this->users->save($byEmail);

            return $byEmail;
        }

        $user = new User;
        $user->name = $social->getName() ?: explode('@', $email)[0];
        $user->email = $email;
        $user->password = null;
        $user->email_verified_at = now();
        $user->{$column} = $id;

        $this->users->save($user);

        return $user;
    }

    public function createExchangeKey(User $user): string
    {
        $token = Str::random(48);
        Cache::put(self::CACHE_PREFIX.$token, $user->id, now()->addSeconds(self::EXCHANGE_TTL_SECONDS));

        return $token;
    }

    public function findUserByExchangeToken(string $exchangeToken): ?User
    {
        $key = self::CACHE_PREFIX.$exchangeToken;

        $userId = Cache::pull($key);

        $id = is_int($userId) ? $userId : (is_numeric($userId) ? (int) $userId : null);

        if ($id === null) {
            return null;
        }

        return $this->users->findById($id);
    }

    public function unlinkProvider(User $user, SocialProvider $provider): void
    {
        $column = $provider->idColumn();
        $currentId = $user->{$column};

        if ($currentId === null || $currentId === '') {
            throw new \RuntimeException(ucfirst($provider->value).' is not linked to this account.');
        }

        $hasPassword = $user->password !== null;
        $remainingSocialCount = $this->countLinkedProviders($user, exclude: $provider);

        if (! $hasPassword && $remainingSocialCount === 0) {
            throw new \InvalidArgumentException(
                'Add a password or keep another social sign-in before unlinking this provider.',
            );
        }

        $user->{$column} = null;
        $this->users->save($user);
    }

    private function countLinkedProviders(User $user, SocialProvider $exclude): int
    {
        $count = 0;

        foreach (SocialProvider::cases() as $provider) {
            if ($provider === $exclude) {
                continue;
            }

            $value = $user->{$provider->idColumn()};

            if ($value !== null && $value !== '') {
                $count++;
            }
        }

        return $count;
    }
}
