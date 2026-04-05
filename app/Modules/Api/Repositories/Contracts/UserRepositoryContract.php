<?php

declare(strict_types=1);

namespace App\Modules\Api\Repositories\Contracts;

use App\Modules\Api\Models\User;

interface UserRepositoryContract
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findByPhone(string $e164Phone): ?User;

    public function findByGoogleId(string $googleId): ?User;

    public function findByGithubId(string $githubId): ?User;

    /**
     * Find a user by any social provider ID column.
     */
    public function findBySocialId(string $column, string $socialId): ?User;

    public function save(User $user): void;

    public function delete(User $user): void;
}
