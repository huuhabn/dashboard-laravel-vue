<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Saas;

use App\Modules\Api\Enums\LoginMethod;
use App\Modules\Api\Enums\OtpDeliverTo;
use App\Shared\Saas\SaasAuth;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SaasAuthTest extends TestCase
{
    public function test_default_values(): void
    {
        Config::set('services.saas.auth', []);

        $this->assertSame(LoginMethod::Both, SaasAuth::loginMethod());
        $this->assertSame(OtpDeliverTo::Email, SaasAuth::otpDeliverTo());
        $this->assertSame('VN', SaasAuth::defaultPhoneRegion());
        $this->assertTrue(SaasAuth::allowsPasswordLogin());
        $this->assertTrue(SaasAuth::allowsOtpLogin());
        $this->assertTrue(SaasAuth::otpAllowsEmail());
        $this->assertFalse(SaasAuth::otpAllowsPhone());
    }

    public function test_login_method_password_only(): void
    {
        Config::set('services.saas.auth', ['login_methods' => 'password_only']);

        $this->assertSame(LoginMethod::PasswordOnly, SaasAuth::loginMethod());
        $this->assertTrue(SaasAuth::allowsPasswordLogin());
        $this->assertFalse(SaasAuth::allowsOtpLogin());
    }

    public function test_login_method_otp_only(): void
    {
        Config::set('services.saas.auth', ['login_methods' => 'otp_only']);

        $this->assertSame(LoginMethod::OtpOnly, SaasAuth::loginMethod());
        $this->assertFalse(SaasAuth::allowsPasswordLogin());
        $this->assertTrue(SaasAuth::allowsOtpLogin());
    }

    public function test_invalid_login_method_falls_back_to_both(): void
    {
        Config::set('services.saas.auth', ['login_methods' => 'invalid_value']);

        $this->assertSame(LoginMethod::Both, SaasAuth::loginMethod());
    }

    public function test_otp_deliver_to_phone(): void
    {
        Config::set('services.saas.auth', ['otp_deliver_to' => 'phone']);

        $this->assertSame(OtpDeliverTo::Phone, SaasAuth::otpDeliverTo());
        $this->assertFalse(SaasAuth::otpAllowsEmail());
        $this->assertTrue(SaasAuth::otpAllowsPhone());
    }

    public function test_otp_deliver_to_both(): void
    {
        Config::set('services.saas.auth', ['otp_deliver_to' => 'both']);

        $this->assertSame(OtpDeliverTo::Both, SaasAuth::otpDeliverTo());
        $this->assertTrue(SaasAuth::otpAllowsEmail());
        $this->assertTrue(SaasAuth::otpAllowsPhone());
    }

    public function test_invalid_otp_deliver_to_falls_back_to_email(): void
    {
        Config::set('services.saas.auth', ['otp_deliver_to' => 'some_rubbish']);

        $this->assertSame(OtpDeliverTo::Email, SaasAuth::otpDeliverTo());
    }

    public function test_public_auth_payload(): void
    {
        Config::set('services.saas.auth', [
            'login_methods' => 'otp_only',
            'otp_deliver_to' => 'phone',
            'default_phone_region' => 'US',
        ]);

        $payload = SaasAuth::publicAuthPayload();

        $this->assertSame('otp_only', $payload['login_methods']);
        $this->assertSame('phone', $payload['otp_deliver_to']);
        $this->assertSame('US', $payload['default_phone_region']);
    }
}
