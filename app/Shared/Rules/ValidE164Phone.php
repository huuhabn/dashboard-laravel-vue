<?php

declare(strict_types=1);

namespace App\Shared\Rules;

use App\Shared\Phone\PhoneNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Ensures the value parses to a valid E.164 number (after normalization).
 */
final class ValidE164Phone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail(__('validation.required', ['attribute' => $attribute]));

            return;
        }

        $e164 = PhoneNormalizer::toE164OrNull(
            $value,
            config('services.saas.auth.default_phone_region', 'VN'),
        );
        if ($e164 === null) {
            $fail(__('The :attribute must be a valid phone number.', ['attribute' => $attribute]));
        }
    }
}
