<?php

namespace Tests\Unit\Support\Travelport;

use App\Support\Travelport\TravelportContactSsrBuilder;
use PHPUnit\Framework\TestCase;

class TravelportContactSsrBuilderTest extends TestCase
{
    public function test_format_ctce_email_replaces_special_characters(): void
    {
        $this->assertSame(
            'JOHN..DOE./JR//TRAVELPORT.COM',
            TravelportContactSsrBuilder::formatCtceEmail('John_Doe-Jr@travelport.com'),
        );
    }

    public function test_format_ctcm_phone_strips_non_digits(): void
    {
        $this->assertSame(
            '9715049211868',
            TravelportContactSsrBuilder::formatCtcmPhone('971', '50', '49211868'),
        );
    }

    public function test_contact_ssrs_builds_ctcm_and_ctce(): void
    {
        $ssrs = TravelportContactSsrBuilder::contactSsrs('971', '50', '49211868', 'john@example.com');

        $this->assertCount(2, $ssrs);
        $this->assertSame('CTCM', $ssrs[0]['type']);
        $this->assertSame('9715049211868', $ssrs[0]['free_text']);
        $this->assertSame('CTCE', $ssrs[1]['type']);
        $this->assertSame('JOHN//EXAMPLE.COM', $ssrs[1]['free_text']);
    }

    public function test_resolve_carrier_from_first_segment(): void
    {
        $carrier = TravelportContactSsrBuilder::resolveCarrierFromPricingData([
            'segments' => [
                ['carrier' => 'ek'],
            ],
        ]);

        $this->assertSame('EK', $carrier);
    }
}
