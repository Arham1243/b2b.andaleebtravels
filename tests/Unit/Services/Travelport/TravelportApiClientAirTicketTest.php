<?php

namespace Tests\Unit\Services\Travelport;

use App\Services\Travelport\TravelportApiClient;
use Tests\TestCase;

class TravelportApiClientAirTicketTest extends TestCase
{
    private TravelportApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new TravelportApiClient();
    }

    public function test_builds_ticket_xml_without_pricing_refs_when_keys_empty(): void
    {
        $xml = $this->client->buildAirTicketRequestXml('367AI4', [], 'EK', 0.0, 'trace-test');

        $this->assertStringContainsString('<AirReservationLocatorCode>367AI4</AirReservationLocatorCode>', $xml);
        $this->assertStringNotContainsString('AirPricingInfoRef', $xml);
        $this->assertStringContainsString('PlatingCarrier="EK"', $xml);
    }

    public function test_builds_single_pricing_ref_before_modifiers(): void
    {
        $xml = $this->client->buildAirTicketRequestXml(
            '367AI4',
            ['hold-key-single'],
            'EK',
            1.5,
            'trace-test',
        );

        $this->assertSame(1, substr_count($xml, 'AirPricingInfoRef'));
        $this->assertStringContainsString(
            '<AirReservationLocatorCode>367AI4</AirReservationLocatorCode>' . "\n" . '            <AirPricingInfoRef Key="hold-key-single"/>',
            $xml,
        );
        $this->assertStringNotContainsString(
            '<AirTicketingModifiers PlatingCarrier="EK">' . "\n" . '                <AirPricingInfoRef',
            $xml,
        );
    }

    public function test_builds_multiple_pricing_refs_inside_modifiers(): void
    {
        $xml = $this->client->buildAirTicketRequestXml(
            '367AI4',
            ['hold-key-adt', 'hold-key-cnn', 'hold-key-inf'],
            'EK',
            0.0,
            'trace-test',
        );

        $this->assertSame(3, substr_count($xml, 'AirPricingInfoRef'));
        $this->assertStringContainsString('<AirPricingInfoRef Key="hold-key-adt"/>', $xml);
        $this->assertStringContainsString('<AirPricingInfoRef Key="hold-key-cnn"/>', $xml);
        $this->assertStringContainsString('<AirPricingInfoRef Key="hold-key-inf"/>', $xml);
        $this->assertStringContainsString('<AirTicketingModifiers PlatingCarrier="EK">', $xml);
        $this->assertStringNotContainsString(
            '<AirReservationLocatorCode>367AI4</AirReservationLocatorCode>' . "\n" . '            <AirPricingInfoRef',
            $xml,
        );
    }
}
