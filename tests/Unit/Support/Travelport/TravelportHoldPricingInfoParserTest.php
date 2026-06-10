<?php

namespace Tests\Unit\Support\Travelport;

use App\Support\Travelport\TravelportHoldPricingInfoParser;
use PHPUnit\Framework\TestCase;

class TravelportHoldPricingInfoParserTest extends TestCase
{
    public function test_extracts_single_key_from_parsed_hold_response(): void
    {
        $holdResponse = [
            'parsed' => [
                'Body' => [
                    'AirCreateReservationRsp' => [
                        'UniversalRecord' => [
                            'AirReservation' => [
                                'AirPricingInfo' => [
                                    '@attributes' => ['Key' => 'hold-key-1'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame(['hold-key-1'], TravelportHoldPricingInfoParser::extractKeys($holdResponse));
    }

    public function test_extracts_multiple_keys_from_parsed_hold_response(): void
    {
        $holdResponse = [
            'UniversalRecord' => [
                'AirReservation' => [
                    'AirPricingInfo' => [
                        ['@attributes' => ['Key' => 'hold-key-adt']],
                        ['@attributes' => ['Key' => 'hold-key-cnn']],
                        ['@attributes' => ['Key' => 'hold-key-inf']],
                    ],
                ],
            ],
        ];

        $this->assertSame(
            ['hold-key-adt', 'hold-key-cnn', 'hold-key-inf'],
            TravelportHoldPricingInfoParser::extractKeys($holdResponse),
        );
    }

    public function test_extracts_keys_from_raw_xml_fallback(): void
    {
        $holdResponse = [
            'raw' => '<universal:AirCreateReservationRsp>'
                . '<universal:UniversalRecord>'
                . '<air:AirReservation>'
                . '<air:AirPricingInfo Key="raw-key-1"/>'
                . '<air:AirPricingInfo Key="raw-key-2"/>'
                . '</air:AirReservation>'
                . '</universal:UniversalRecord>'
                . '</universal:AirCreateReservationRsp>',
        ];

        $this->assertSame(['raw-key-1', 'raw-key-2'], TravelportHoldPricingInfoParser::extractKeys($holdResponse));
    }

    public function test_resolver_prefers_persisted_hold_keys_over_air_price_key(): void
    {
        $keys = TravelportHoldPricingInfoParser::resolveKeysForTicketing(
            [
                'hold_air_pricing_info_keys' => ['persisted-hold-key'],
                'pricing_data' => ['pricing_info_key' => 'stale-airprice-key'],
            ],
            [
                'UniversalRecord' => [
                    'AirReservation' => [
                        'AirPricingInfo' => ['@attributes' => ['Key' => 'response-hold-key']],
                    ],
                ],
            ],
        );

        $this->assertSame(['persisted-hold-key'], $keys);
    }

    public function test_resolver_uses_hold_response_before_air_price_key(): void
    {
        $keys = TravelportHoldPricingInfoParser::resolveKeysForTicketing(
            [
                'pricing_data' => ['pricing_info_key' => 'stale-airprice-key'],
            ],
            [
                'UniversalRecord' => [
                    'AirReservation' => [
                        'AirPricingInfo' => ['@attributes' => ['Key' => 'response-hold-key']],
                    ],
                ],
            ],
        );

        $this->assertSame(['response-hold-key'], $keys);
    }

    public function test_resolver_falls_back_to_air_price_key_when_hold_missing(): void
    {
        $keys = TravelportHoldPricingInfoParser::resolveKeysForTicketing(
            [
                'pricing_data' => ['pricing_info_key' => 'airprice-fallback-key'],
            ],
            [],
        );

        $this->assertSame(['airprice-fallback-key'], $keys);
    }

    public function test_resolver_returns_empty_when_no_keys_available(): void
    {
        $this->assertSame([], TravelportHoldPricingInfoParser::resolveKeysForTicketing([], []));
    }
}
