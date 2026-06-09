<?php

namespace Tests\Unit\Support;

use App\Support\SabrePriceQuoteResolver;
use PHPUnit\Framework\TestCase;

class SabrePriceQuoteResolverTest extends TestCase
{
    public function test_extracts_price_quote_numbers_from_booking_response(): void
    {
        $response = [
            'CreatePassengerNameRecordRS' => [
                'TravelItineraryRead' => [
                    'TravelItinerary' => [
                        'ItineraryInfo' => [
                            'ItineraryPricing' => [
                                'PriceQuote' => [
                                    [
                                        'RPH' => '1',
                                        'MiscInformation' => [
                                            'SignatureLine' => [
                                                ['Status' => 'ACTIVE'],
                                            ],
                                        ],
                                    ],
                                    [
                                        'RPH' => '2',
                                        'MiscInformation' => [
                                            'SignatureLine' => [
                                                ['Status' => 'INACTIVE'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame([1], SabrePriceQuoteResolver::fromBookingResponse($response));
    }

    public function test_extracts_price_quote_numbers_from_xml(): void
    {
        $xml = <<<'XML'
<PriceQuote RPH="1">
  <MiscInformation>
    <SignatureLine Status="ACTIVE"/>
  </MiscInformation>
</PriceQuote>
<PriceQuote RPH="2">
  <MiscInformation>
    <SignatureLine Status="HISTORY"/>
  </MiscInformation>
</PriceQuote>
XML;

        $this->assertSame([1], SabrePriceQuoteResolver::fromXml($xml));
    }

    public function test_builds_consecutive_air_ticket_price_quote_payload(): void
    {
        $payload = SabrePriceQuoteResolver::buildAirTicketPriceQuotePayload([1, 2, 3]);

        $this->assertSame([
            [
                'Record' => [
                    [
                        'Number' => 1,
                        'EndNumber' => 3,
                    ],
                ],
            ],
        ], $payload);
    }

    public function test_builds_non_consecutive_air_ticket_price_quote_payload(): void
    {
        $payload = SabrePriceQuoteResolver::buildAirTicketPriceQuotePayload([1, 3]);

        $this->assertSame([
            [
                'Record' => [
                    ['Number' => 1],
                    ['Number' => 3],
                ],
            ],
        ], $payload);
    }
}
