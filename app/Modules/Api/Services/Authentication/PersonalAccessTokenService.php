<?php

declare(strict_types=1);

namespace App\Modules\Api\Services\Authentication;

use App\Modules\Api\DTOs\TokenResult;
use App\Modules\Api\Events\PersonalAccessTokenIssued;
use App\Modules\Api\Models\User;
use App\Modules\Api\Repositories\Contracts\UserRepositoryContract;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

/**
 * Sanctum personal access tokens: password check, issue, revoke current.
 */
final class PersonalAccessTokenService
{
    public function __construct(
        private readonly UserRepositoryContract $users,
    ) {}

    public function validatePasswordCredentials(string $email, string $password): ?User
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || $user->password === null) {
            return null;
        }

        if (! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    public function issueForUser(User $user, string $deviceName): TokenResult
    {
        $plainTextToken = $user->createToken($deviceName)->plainTextToken;

        Event::dispatch(new PersonalAccessTokenIssued($user->id));

        return new TokenResult(
            user: $user,
            plainTextToken: $plainTextToken,
        );
    }

    public function issueFromPasswordLogin(string $email, string $password, string $deviceName): ?TokenResult
    {
        $user = $this->validatePasswordCredentials($email, $password);

        if ($user === null) {
            return null;
        }

        return $this->issueForUser($user, $deviceName);
    }

    public function revokeCurrent(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
