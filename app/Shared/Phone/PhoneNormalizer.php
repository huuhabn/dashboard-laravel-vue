<?php

declare(strict_types=1);

namespace App\Shared\Phone;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Normalizes user-supplied phone strings to E.164 using libphonenumber.
 */
final class PhoneNormalizer
{
    public static function toE164OrNull(string $input, ?string $defaultRegion): ?string
    {
        $trimmed = trim($input);
        if ($trimmed === '') {
            return null;
        }

        $util = PhoneNumberUtil::getInstance();
        $region = $defaultRegion !== null && $defaultRegion !== ''
            ? strtoupper($defaultRegion)
            : null;

        try {
            $parsed = $util->parse($trimmed, $region);
        } catch (NumberParseException) {
            return null;
        }

        if (! $util->isValidNumber($parsed)) {
            return null;
        }

        return $util->format($parsed, PhoneNumberFormat::E164);
    }
}
