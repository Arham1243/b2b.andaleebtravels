<?php

namespace Tests\Unit\Support\Travelport;

use App\Support\Travelport\TravelportHoldPayloadBuilder;
use PHPUnit\Framework\TestCase;

class TravelportHoldPayloadBuilderPhoneTest extends TestCase
{
    public function test_split_uae_mobile_number_for_travelport(): void
    {
        $parts = TravelportHoldPayloadBuilder::splitLocalNumber('971', '501234567');

        $this->assertSame('971', $parts['country']);
        $this->assertSame('50', $parts['area']);
        $this->assertSame('1234567', $parts['number']);
    }

    public function test_normalize_lead_phone_from_dial_code_and_local_fields(): void
    {
        $lead = TravelportHoldPayloadBuilder::normalizeLeadPhone([
            'phone_dial_code' => '971',
            'phone_local' => '501234567',
        ]);

        $this->assertSame('971', $lead['phone_dial_code']);
        $this->assertSame('50', $lead['phone_area_code']);
        $this->assertSame('1234567', $lead['phone_number']);
        $this->assertSame('+971501234567', $lead['phone']);
    }

    public function test_parse_combined_international_number_with_three_digit_dial_code(): void
    {
        $parts = TravelportHoldPayloadBuilder::resolvePhoneFromLead([
            'phone' => '+971501234567',
        ]);

        $this->assertSame('971', $parts['country']);
        $this->assertSame('50', $parts['area']);
        $this->assertSame('1234567', $parts['number']);
    }

    public function test_parse_lead_phone_for_form_prefills_dial_and_local(): void
    {
        $form = TravelportHoldPayloadBuilder::parseLeadPhoneForForm('+971501234567');

        $this->assertSame('971', $form['dial_code']);
        $this->assertSame('501234567', $form['local']);
        $this->assertSame('AE', $form['iso']);
    }
}
