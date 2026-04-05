<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Modules\Api\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_begin_two_factor_setup(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/me/two-factor');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'secret',
                    'otpauth_url',
                ],
            ]);

        $this->assertNotEmpty($user->fresh()->two_factor_secret);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_setup_cannot_begin_if_already_enabled(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'DUMMYSECRET12345',
            'two_factor_confirmed_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/two-factor')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['two_factor']);
    }

    public function test_user_can_confirm_two_factor_setup(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'two_factor_secret' => $secret,
        ]);
        Sanctum::actingAs($user);

        $code = $google2fa->getCurrentOtp($secret);

        $response = $this->postJson('/api/v1/me/two-factor/confirm', [
            'code' => $code,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'recovery_codes',
                    'user',
                ],
            ]);

        $this->assertCount(8, $response->json('data.recovery_codes'));
        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->assertNotEmpty($user->fresh()->two_factor_recovery_codes);
    }

    public function test_confirm_fails_with_invalid_code(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'two_factor_secret' => $secret,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/two-factor/confirm', [
            'code' => '000000',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);

        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_confirm_fails_if_not_started(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/two-factor/confirm', [
            'code' => '123456',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['two_factor']);
    }

    public function test_user_can_disable_two_factor(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['dummy'],
        ]);
        Sanctum::actingAs($user);

        $code = $google2fa->getCurrentOtp($secret);

        $this->deleteJson('/api/v1/me/two-factor', [
            'code' => $code,
            'current_password' => 'password',
        ])->assertOk();

        $fresh = $user->fresh();
        $this->assertNull($fresh->two_factor_secret);
        $this->assertNull($fresh->two_factor_confirmed_at);
        $this->assertNull($fresh->two_factor_recovery_codes);
    }

    public function test_disable_fails_with_invalid_password(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $code = $google2fa->getCurrentOtp($secret);

        $this->deleteJson('/api/v1/me/two-factor', [
            'code' => $code,
            'current_password' => 'wrong',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    }
}
