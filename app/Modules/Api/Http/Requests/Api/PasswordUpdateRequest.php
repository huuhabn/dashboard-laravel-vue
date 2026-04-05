<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Requests\Api;

use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class PasswordUpdateRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        $rules = [
            'password' => $this->passwordRules(),
        ];

        if ($user !== null && $user->hasPassword()) {
            $rules['current_password'] = $this->currentPasswordRules();
        } else {
            $rules['current_password'] = ['prohibited'];
        }

        return $rules;
    }
}
