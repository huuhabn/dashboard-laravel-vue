<?php

declare(strict_types=1);

namespace App\Modules\Api\Repositories;

use App\Modules\Api\Models\User;
use App\Modules\Api\Repositories\Contracts\UserRepositoryContract;

final class UserRepository implements UserRepositoryContract
{
    public function findById(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        $normalized = mb_strtolower(trim($email));

        return User::query()->whereRaw('lower(email) = ?', [$normalized])->first();
    }

    public function findByPhone(string $e164Phone): ?User
    {
        return User::query()->where('phone', $e164Phone)->first();
    }

    public function findByGoogleId(string $googleId): ?User
    {
        return User::query()->where('google_id', $googleId)->first();
    }

    public function findByGithubId(string $githubId): ?User
    {
        return User::query()->where('github_id', $githubId)->first();
    }

    public function findBySocialId(string $column, string $socialId): ?User
    {
        return User::query()->where($column, $socialId)->first();
    }

    public function save(User $user): void
    {
        $user->save();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
