<?php

declare(strict_types=1);

namespace App\Shared\Storage;

use App\Modules\Api\Models\User;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

/**
 * Removes avatar objects from the configured avatar disk when the URL matches
 * that disk's public base URL and path avatars/{user_id}/...
 */
final class UserAvatarStorageCleaner
{
    public function __construct(
        private readonly FilesystemFactory $filesystem,
    ) {}

    public function deleteManagedFileForUrl(User $user, ?string $url): void
    {
        if ($url === null || $url === '') {
            return;
        }

        $diskName = (string) config('services.saas.avatar_disk', 'public');
        $disk = $this->filesystem->disk($diskName);
        $baseUrl = rtrim((string) config("filesystems.disks.{$diskName}.url", ''), '/');
        if ($baseUrl === '' || ! str_starts_with($url, $baseUrl.'/')) {
            return;
        }

        $relative = substr($url, strlen($baseUrl) + 1);
        $prefix = 'avatars/'.$user->id.'/';

        if (! str_starts_with($relative, $prefix)) {
            return;
        }

        $disk->delete($relative);
    }
}
