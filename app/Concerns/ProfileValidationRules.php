<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Modules\Api\Models\User;
use App\Shared\Rules\ValidE164Phone;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
            'avatar' => $this->avatarRules(),
            'phone' => $this->phoneRules($userId),
        ];
    }

    /**
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function avatarRules(): array
    {
        return ['nullable', 'string', 'max:2048', 'url'];
    }

    /**
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function phoneRules(?int $userId = null): array
    {
        return [
            'nullable',
            'string',
            'max:32',
            new ValidE164Phone,
            $userId === null
                ? Rule::unique(User::class, 'phone')
                : Rule::unique(User::class, 'phone')->ignore($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
