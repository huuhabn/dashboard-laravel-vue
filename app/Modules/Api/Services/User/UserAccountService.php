<?php

declare(strict_types=1);

namespace App\Modules\Api\Services\User;

use App\Modules\Api\Events\UserAccountDeleted;
use App\Modules\Api\Events\UserPasswordChanged;
use App\Modules\Api\Events\UserProfileUpdated;
use App\Modules\Api\Models\User;
use App\Modules\Api\Repositories\Contracts\UserRepositoryContract;
use App\Shared\Storage\UserAvatarStorageCleaner;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * Registration, profile, avatar upload, password change, account deletion.
 */
final class UserAccountService
{
    public function __construct(
        private readonly UserRepositoryContract $users,
        private readonly FilesystemFactory $filesystem,
        private readonly UserAvatarStorageCleaner $avatarCleaner,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string, avatar?: string|null, phone?: string|null}  $validated
     */
    public function register(array $validated): User
    {
        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'avatar' => $validated['avatar'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        $this->users->save($user);

        Event::dispatch(new Registered($user));

        return $user;
    }

    /**
     * @param  array{name: string, email: string, avatar?: string|null, phone?: string|null}  $validated
     */
    public function updateProfile(User $user, array $validated): User
    {
        if (array_key_exists('avatar', $validated)) {
            $newAvatar = $validated['avatar'];
            $oldAvatar = $user->avatar;
            if ($oldAvatar !== $newAvatar) {
                $this->avatarCleaner->deleteManagedFileForUrl($user, $oldAvatar);
            }
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $this->users->save($user);

        Event::dispatch(new UserProfileUpdated($user->id));

        return $user;
    }

    public function uploadAvatar(User $user, UploadedFile $file): User
    {
        $diskName = (string) config('services.saas.avatar_disk', 'public');
        $disk = $this->filesystem->disk($diskName);

        $this->avatarCleaner->deleteManagedFileForUrl($user, $user->avatar);

        $extension = $file->guessExtension() ?: 'jpg';
        $filename = (string) Str::uuid().'.'.$extension;
        $directory = 'avatars/'.$user->id;

        $path = $disk->putFileAs($directory, $file, $filename, ['visibility' => 'public']);

        if ($path === false) {
            throw new \RuntimeException('Could not store avatar file.');
        }

        $user->avatar = $disk->url($path);
        $this->users->save($user);

        Event::dispatch(new UserProfileUpdated($user->id));

        return $user;
    }

    public function changePassword(User $user, string $password): User
    {
        $user->password = $password;

        $this->users->save($user);

        Event::dispatch(new UserPasswordChanged($user->id));

        return $user;
    }

    public function deleteAccount(User $user): void
    {
        $userId = $user->id;

        $user->tokens()->delete();

        $this->users->delete($user);

        Event::dispatch(new UserAccountDeleted($userId));
    }
}
