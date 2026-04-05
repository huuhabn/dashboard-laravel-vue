<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Storage;

use App\Modules\Api\Models\User;
use App\Shared\Storage\UserAvatarStorageCleaner;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserAvatarStorageCleanerTest extends TestCase
{
    use RefreshDatabase;

    private UserAvatarStorageCleaner $cleaner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cleaner = new UserAvatarStorageCleaner(
            $this->app->make(FilesystemManager::class)
        );
    }

    public function test_removes_old_avatar_if_it_is_local(): void
    {
        $user = User::factory()->create(['id' => 123]);

        Config::set('services.saas.avatar_disk', 'public');
        Config::set('filesystems.disks.public.url', 'http://localhost/storage');

        Storage::fake('public');
        Storage::disk('public')->put('avatars/123/test.jpg', 'content');

        $this->assertTrue(Storage::disk('public')->exists('avatars/123/test.jpg'));

        $this->cleaner->deleteManagedFileForUrl($user, 'http://localhost/storage/avatars/123/test.jpg');

        $this->assertFalse(Storage::disk('public')->exists('avatars/123/test.jpg'));
    }

    public function test_ignores_non_local_avatar(): void
    {
        $user = User::factory()->create(['id' => 123]);

        Config::set('services.saas.avatar_disk', 'public');
        Config::set('filesystems.disks.public.url', 'http://localhost/storage');

        Storage::fake('public');
        Storage::disk('public')->put('avatars/123/test.jpg', 'content');

        // This is not an avatar stored locally in our way
        $this->cleaner->deleteManagedFileForUrl($user, 'https://lh3.googleusercontent.com/a/dummy-avatar');

        $this->assertTrue(Storage::disk('public')->exists('avatars/123/test.jpg'));
    }

    public function test_ignores_empty_or_null_avatar(): void
    {
        $user = User::factory()->create(['id' => 123]);
        Storage::fake('public');

        // Should not throw exceptions
        $this->cleaner->deleteManagedFileForUrl($user, null);
        $this->cleaner->deleteManagedFileForUrl($user, '');

        $this->assertTrue(true);
    }

    public function test_ignores_avatar_from_different_user(): void
    {
        $user = User::factory()->create(['id' => 123]);

        Config::set('services.saas.avatar_disk', 'public');
        Config::set('filesystems.disks.public.url', 'http://localhost/storage');

        Storage::fake('public');
        Storage::disk('public')->put('avatars/999/test.jpg', 'content');

        $this->assertTrue(Storage::disk('public')->exists('avatars/999/test.jpg'));

        $this->cleaner->deleteManagedFileForUrl($user, 'http://localhost/storage/avatars/999/test.jpg');

        // Should still exist because $user->id is 123
        $this->assertTrue(Storage::disk('public')->exists('avatars/999/test.jpg'));
    }
}
