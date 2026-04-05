<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class SocialExchangeRequest extends FormRequest
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
            'exchange_token' => ['required', 'string', 'max:128'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
