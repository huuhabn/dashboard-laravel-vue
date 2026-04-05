<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

final class ProfileDeleteRequest extends FormRequest
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
            'password' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            if ($user === null) {
                return;
            }

            if ($user->password === null) {
                $validator->errors()->add(
                    'password',
                    'Set a password in Security settings before you can delete your account.',
                );

                return;
            }

            if (! Hash::check($this->input('password'), $user->password)) {
                $validator->errors()->add('password', __('This password does not match our records.'));
            }
        });
    }
}
