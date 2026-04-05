<?php

namespace Tests\Feature\Settings;

use App\Modules\Api\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AvatarUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_upload_avatar(): void
    {
        Storage::fake('public');
        config(['services.saas.avatar_disk' => 'public']);

        $file = UploadedFile::fake()->image('a.jpg', 40, 40);

        $this->post('/api/v1/me/avatar', [
            'avatar' => $file,
        ], ['Accept' => 'application/json'])
            ->assertUnauthorized();
    }

    public function test_user_can_upload_avatar(): void
    {
        Storage::fake('public');
        config(['services.saas.avatar_disk' => 'public']);

        $user = User::factory()->create(['avatar' => null]);
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('photo.jpg', 80, 80);

        $response = $this->post('/api/v1/me/avatar', [
            'avatar' => $file,
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->assertStringContainsString('avatars/'.$user->id, $user->avatar);

        $paths = Storage::disk('public')->files('avatars/'.$user->id);
        $this->assertCount(1, $paths);
    }

    public function test_upload_rejects_non_image(): void
    {
        Storage::fake('public');
        config(['services.saas.avatar_disk' => 'public']);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->post('/api/v1/me/avatar', [
            'avatar' => $file,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['avatar']);
    }

    public function test_profile_patch_clearing_avatar_deletes_managed_file(): void
    {
        Storage::fake('public');
        config(['services.saas.avatar_disk' => 'public']);

        $user = User::factory()->create();
        $path = 'avatars/'.$user->id.'/stored.jpg';
        Storage::disk('public')->put($path, 'fake-binary');
        $base = rtrim((string) config('filesystems.disks.public.url'), '/');
        $user->avatar = $base.'/'.$path;
        $user->save();

        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me', [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => null,
            'phone' => null,
        ])->assertOk();

        Storage::disk('public')->assertMissing($path);
    }

    public function test_second_upload_deletes_previous_managed_avatar_file(): void
    {
        Storage::fake('public');
        config(['services.saas.avatar_disk' => 'public']);

        $user = User::factory()->create();
        $oldPath = 'avatars/'.$user->id.'/old.jpg';
        Storage::disk('public')->put($oldPath, 'fake-binary');
        $base = rtrim((string) config('filesystems.disks.public.url'), '/');
        $user->avatar = $base.'/'.$oldPath;
        $user->save();

        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('new.jpg', 40, 40);

        $this->post('/api/v1/me/avatar', [
            'avatar' => $file,
        ], ['Accept' => 'application/json'])->assertOk();

        Storage::disk('public')->assertMissing($oldPath);

        $paths = Storage::disk('public')->files('avatars/'.$user->id);
        $this->assertCount(1, $paths);
    }
}
