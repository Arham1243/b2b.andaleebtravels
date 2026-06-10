<?php

namespace Tests\Unit\Support;

use App\Support\FlightCabinPreference;
use PHPUnit\Framework\TestCase;

class FlightCabinPreferenceTest extends TestCase
{
    public function test_falcon_gold_booking_class_d_resolves_to_business(): void
    {
        $this->assertSame(
            'Business',
            FlightCabinPreference::resolveCabinFamily('Falcon Gold Smart', 'D', 'Economy'),
        );
    }

    public function test_falcon_gold_brand_name_resolves_to_business_without_booking_code(): void
    {
        $this->assertSame(
            'Business',
            FlightCabinPreference::resolveCabinFamily('Falcon Gold Flex', null, null),
        );
    }

    public function test_falcon_gold_fare_does_not_match_economy_search(): void
    {
        $fare = [
            'fare_brand' => 'Falcon Gold Smart',
            'fare_basis' => 'DBSMR3AE',
            'booking_code' => 'D',
            'cabin_code' => 'Business',
            'fare_rules' => [
                'components' => [
                    ['cabin' => 'Business'],
                ],
            ],
        ];

        $this->assertFalse(FlightCabinPreference::fareMatchesSearch($fare, 'Economy', 'Economy', 'one_way'));
    }

    public function test_mislabeled_falcon_gold_still_excluded_from_economy_search(): void
    {
        $fare = [
            'fare_brand' => 'Falcon Gold Smart',
            'fare_basis' => 'DBSMR3AE',
            'booking_code' => 'D',
            'cabin_code' => 'Economy',
            'fare_rules' => [
                'components' => [
                    ['cabin' => 'Economy'],
                ],
            ],
        ];

        $this->assertFalse(FlightCabinPreference::fareMatchesSearch($fare, 'Economy', 'Economy', 'one_way'));
    }

    public function test_economy_smart_fare_still_matches_economy_search(): void
    {
        $fare = [
            'fare_brand' => 'Economy Smart',
            'fare_basis' => 'WDSMR3AE',
            'booking_code' => 'W',
            'cabin_code' => 'Economy',
            'fare_rules' => [
                'components' => [
                    ['cabin' => 'Economy'],
                ],
            ],
        ];

        $this->assertTrue(FlightCabinPreference::fareMatchesSearch($fare, 'Economy', 'Economy', 'one_way'));
    }
}
