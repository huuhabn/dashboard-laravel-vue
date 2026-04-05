<?php

namespace Tests\Feature\Auth;

use App\Modules\Api\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_spa_shell_is_served(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertViewIs('app');
    }

    public function test_reset_password_link_can_be_requested_via_api(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
            ->assertOk();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset_web_route_redirects_to_spa_with_query(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->get(route('password.reset', [
                'token' => $notification->token,
                'email' => $user->email,
            ]));

            $response->assertRedirect();
            $this->assertStringContainsString('/reset-password', $response->headers->get('Location'));

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token_via_api(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->postJson('/api/v1/auth/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])->assertOk();

            return true;
        });

        $user->refresh();
        $this->assertTrue(Hash::check('new-password-123', $user->password));
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
