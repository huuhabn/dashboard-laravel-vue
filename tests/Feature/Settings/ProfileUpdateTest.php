<?php

namespace Tests\Feature\Settings;

use App\Modules\Api\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_information_can_be_updated_via_api(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Test User')
            ->assertJsonPath('data.email', 'test@example.com');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me', [
            'name' => 'Test User',
            'email' => $user->email,
        ])->assertOk();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account_via_api(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/me', [
            'password' => 'password',
        ])
            ->assertNoContent();

        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/me', [
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->assertNotNull($user->fresh());
    }
}
