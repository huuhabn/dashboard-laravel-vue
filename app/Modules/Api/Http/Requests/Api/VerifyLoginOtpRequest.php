<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Requests\Api;

use App\Modules\Api\Enums\OtpDeliverTo;
use App\Shared\Phone\PhoneNormalizer;
use App\Shared\Rules\ValidE164Phone;
use App\Shared\Saas\SaasAuth;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class VerifyLoginOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return SaasAuth::allowsOtpLogin();
    }

    protected function prepareForValidation(): void
    {
        if (! SaasAuth::allowsOtpLogin()) {
            return;
        }

        $phone = $this->input('phone');
        if (! is_string($phone) || trim($phone) === '') {
            return;
        }

        $e164 = PhoneNormalizer::toE164OrNull($phone, SaasAuth::defaultPhoneRegion());
        if ($e164 !== null) {
            $this->merge(['phone' => $e164]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $deliver = SaasAuth::otpDeliverTo();

        $identifierRules = match ($deliver) {
            OtpDeliverTo::Email => [
                'email' => ['required', 'string', 'email', 'max:255'],
            ],
            OtpDeliverTo::Phone => [
                'phone' => ['required', 'string', 'max:32', new ValidE164Phone],
            ],
            default => [
                'otp_to' => ['required', 'string', Rule::in(['email', 'phone'])],
                'email' => [
                    Rule::requiredIf(fn () => $this->input('otp_to') === 'email'),
                    'string',
                    'email',
                    'max:255',
                ],
                'phone' => [
                    Rule::requiredIf(fn () => $this->input('otp_to') === 'phone'),
                    'string',
                    'max:32',
                    new ValidE164Phone,
                ],
            ],
        };

        return array_merge($identifierRules, [
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);
    }
}
