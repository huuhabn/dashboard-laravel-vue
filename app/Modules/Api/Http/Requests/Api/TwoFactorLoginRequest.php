<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class TwoFactorLoginRequest extends FormRequest
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
            'pending_token' => ['required', 'string', 'max:128'],
            'code' => ['required', 'string', 'max:32'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
