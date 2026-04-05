<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class AvatarUploadRequest extends FormRequest
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
            'avatar' => [
                'required',
                'image',
                'max:2048',
                'mimes:jpeg,jpg,png,webp,gif',
            ],
        ];
    }
}
