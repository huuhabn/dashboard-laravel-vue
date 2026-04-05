<?php

namespace Tests\Feature\Api;

use App\Modules\Api\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SocialAccountUnlinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_unlink_social(): void
    {
        $this->deleteJson('/api/v1/me/social/google')->assertUnauthorized();
    }

    public function test_unlink_google_succeeds_when_user_has_password(): void
    {
        $user = User::factory()->create([
            'google_id' => 'g-test-1',
        ]);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/me/social/google')
            ->assertOk()
            ->assertJsonPath('user.google_linked', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'google_id' => null,
        ]);
    }

    public function test_unlink_google_succeeds_when_user_has_github_and_no_password(): void
    {
        $user = User::factory()->create([
            'password' => null,
            'google_id' => 'g-test-2',
            'github_id' => 'gh-test-2',
        ]);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/me/social/google')
            ->assertOk()
            ->assertJsonPath('user.google_linked', false)
            ->assertJsonPath('user.github_linked', true);
    }

    public function test_unlink_fails_when_only_social_provider_and_no_password(): void
    {
        $user = User::factory()->create([
            'password' => null,
            'google_id' => 'g-test-3',
            'github_id' => null,
        ]);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/me/social/google')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['provider']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'google_id' => 'g-test-3',
        ]);
    }

    public function test_unlink_fails_when_provider_not_linked(): void
    {
        $user = User::factory()->create([
            'google_id' => null,
        ]);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/me/social/google')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['provider']);
    }

    public function test_invalid_provider_returns_not_found(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/me/social/facebook')->assertNotFound();
    }
}
