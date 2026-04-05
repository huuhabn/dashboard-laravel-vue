<?php

namespace Tests\Feature\Api;

use App\Modules\Api\Mail\LoginOtpMail;
use App\Modules\Api\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class RestApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_token_and_user(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'device_name' => 'phpunit',
        ]);

        $response->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['token', 'token_type', 'user']);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    public function test_token_endpoint_issues_bearer_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['token', 'token_type', 'user']);
    }

    public function test_token_endpoint_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_token_endpoint_returns_pending_token_when_two_factor_enabled(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [],
        ]);
        $user->save();

        $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'phpunit',
        ])
            ->assertOk()
            ->assertJson([
                'two_factor_required' => true,
            ])
            ->assertJsonStructure(['pending_token']);
    }

    public function test_login_otp_request_sends_mail_for_existing_user(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/otp/request', [
            'email' => $user->email,
        ])
            ->assertOk()
            ->assertJsonStructure(['message', 'resend_available_at'])
            ->assertJsonFragment([
                'message' => 'If an account exists, a sign-in code was sent.',
            ]);

        $this->assertNotNull($response->json('resend_available_at'));

        Mail::assertSent(LoginOtpMail::class, function (LoginOtpMail $mail) use ($user): bool {
            return $mail->hasTo($user->email);
        });
    }

    public function test_login_otp_verify_issues_token(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/otp/request', [
            'email' => $user->email,
        ])->assertOk();

        $code = '';
        Mail::assertSent(LoginOtpMail::class, function (LoginOtpMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $this->assertSame(6, strlen($code));

        $this->postJson('/api/v1/auth/otp/verify', [
            'email' => $user->email,
            'code' => $code,
            'device_name' => 'phpunit',
        ])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['token', 'token_type', 'user']);
    }

    public function test_login_otp_request_returns_same_message_for_unknown_email(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/otp/request', [
            'email' => 'nobody@example.com',
        ])
            ->assertOk()
            ->assertJson([
                'message' => 'If an account exists, a sign-in code was sent.',
                'resend_available_at' => null,
            ]);

        Mail::assertNothingSent();
    }

    public function test_login_otp_request_cooldown_returns_retry_after_seconds(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/otp/request', [
            'email' => $user->email,
        ])->assertOk();

        $second = $this->postJson('/api/v1/auth/otp/request', [
            'email' => $user->email,
        ]);

        $second->assertStatus(422)
            ->assertJsonStructure(['retry_after_seconds', 'errors']);

        $wait = $second->json('retry_after_seconds');
        $this->assertIsInt($wait);
        $this->assertGreaterThan(0, $wait);
        $this->assertLessThanOrEqual(60, $wait);
    }

    public function test_two_factor_login_completes_with_valid_totp(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [],
        ]);
        $user->save();

        $step1 = $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'phpunit',
        ]);

        $pending = $step1->json('pending_token');
        $this->assertNotEmpty($pending);

        $code = $google2fa->getCurrentOtp($secret);

        $this->postJson('/api/v1/auth/token/two-factor', [
            'pending_token' => $pending,
            'code' => $code,
            'device_name' => 'phpunit',
        ])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_dashboard_returns_overview(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonStructure(['welcome_title', 'panels']);
    }

    public function test_revoke_token_endpoint_deletes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->deleteJson('/api/v1/auth/token', [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'name' => 'test',
        ]);
    }

    public function test_auth_config_returns_saas_flags(): void
    {
        $this->getJson('/api/v1/auth/config')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'login_methods',
                    'otp_deliver_to',
                    'default_phone_region',
                ],
            ]);
    }

    public function test_login_otp_forbidden_when_password_only(): void
    {
        Config::set('services.saas.auth.login_methods', 'password_only');

        $this->postJson('/api/v1/auth/otp/request', [
            'email' => 'any@example.com',
        ])->assertForbidden();
    }

    public function test_password_login_forbidden_when_otp_only(): void
    {
        Config::set('services.saas.auth.login_methods', 'otp_only');

        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'phpunit',
        ])->assertForbidden();
    }

    public function test_login_otp_request_logs_sms_when_otp_deliver_to_phone(): void
    {
        Config::set('services.saas.auth.otp_deliver_to', 'phone');

        Log::spy();

        $user = User::factory()->create();
        $user->forceFill(['phone' => '+12025550123']);
        $user->save();

        $this->postJson('/api/v1/auth/otp/request', [
            'phone' => '+12025550123',
        ])->assertOk();

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context): bool {
            return str_contains($message, 'Login OTP SMS')
                && isset($context['code'])
                && strlen((string) $context['code']) === 6;
        });
    }
}
