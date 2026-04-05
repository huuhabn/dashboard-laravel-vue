<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class DisableTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32'],
            'current_password' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            if ($user !== null && $user->hasPassword() && ($this->input('current_password') === null || $this->input('current_password') === '')) {
                $validator->errors()->add('current_password', __('validation.required', ['attribute' => 'current password']));
            }
        });
    }
}
