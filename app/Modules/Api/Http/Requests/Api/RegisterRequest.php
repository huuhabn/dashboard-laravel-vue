<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Requests\Api;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Shared\Phone\PhoneNormalizer;
use App\Shared\Saas\SaasAuth;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $avatar = $this->input('avatar');
        if ($avatar === '') {
            $this->merge(['avatar' => null]);
        }

        $phone = $this->input('phone');
        if ($phone === '' || $phone === null) {
            $this->merge(['phone' => null]);

            return;
        }

        if (! is_string($phone)) {
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
        return [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
