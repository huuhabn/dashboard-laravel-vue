<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Phone;

use App\Shared\Phone\PhoneNormalizer;
use Tests\TestCase;

class PhoneNormalizerTest extends TestCase
{
    public function test_normalizes_vietnamese_phone(): void
    {
        $this->assertSame('+84901234567', PhoneNormalizer::toE164OrNull('0901234567', 'VN'));
        $this->assertSame('+84901234567', PhoneNormalizer::toE164OrNull('+84901234567', 'VN'));
        $this->assertSame('+84901234567', PhoneNormalizer::toE164OrNull('84901234567', 'VN'));
    }

    public function test_normalizes_us_phone(): void
    {
        $this->assertSame('+12025550123', PhoneNormalizer::toE164OrNull('202-555-0123', 'US'));
        $this->assertSame('+12025550123', PhoneNormalizer::toE164OrNull('+1 202 555 0123', 'US'));
    }

    public function test_returns_null_for_invalid_phone(): void
    {
        $this->assertNull(PhoneNormalizer::toE164OrNull('invalid', 'VN'));
        $this->assertNull(PhoneNormalizer::toE164OrNull('123', 'US'));
    }

    public function test_empty_string_returns_null(): void
    {
        $this->assertNull(PhoneNormalizer::toE164OrNull('', 'VN'));
        $this->assertNull(PhoneNormalizer::toE164OrNull('   ', 'VN'));
    }

    public function test_normalizes_without_default_region(): void
    {
        // If no region is provided but it has an international format (+1)
        $this->assertSame('+12025550123', PhoneNormalizer::toE164OrNull('+12025550123', null));

        // Without region and without international format, it might fail to parse correctly
        $this->assertNull(PhoneNormalizer::toE164OrNull('2025550123', null));
    }
}
