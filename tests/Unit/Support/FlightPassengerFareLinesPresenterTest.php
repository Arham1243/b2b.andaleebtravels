<?php

namespace Tests\Unit\Support;

use App\Models\B2bFlightBooking;
use App\Support\FlightPassengerFareLinesPresenter;
use PHPUnit\Framework\TestCase;

class FlightPassengerFareLinesPresenterTest extends TestCase
{
    public function test_lines_match_expected_total_within_tolerance(): void
    {
        $lines = [
            ['type_key' => 'adult', 'count' => 1, 'base_per_pax' => 200.0, 'tax_per_pax' => 325.0],
            ['type_key' => 'child', 'count' => 2, 'base_per_pax' => 150.0, 'tax_per_pax' => 325.0],
            ['type_key' => 'infant', 'count' => 1, 'base_per_pax' => 20.0, 'tax_per_pax' => 0.0],
        ];

        $this->assertTrue(FlightPassengerFareLinesPresenter::linesMatchExpectedTotal($lines, 1495.0));
    }

    public function test_lines_do_not_match_when_child_fares_are_stale_search_estimates(): void
    {
        $lines = [
            ['type_key' => 'adult', 'count' => 1, 'base_per_pax' => 200.0, 'tax_per_pax' => 325.0],
            ['type_key' => 'child', 'count' => 2, 'base_per_pax' => 100.0, 'tax_per_pax' => 162.5],
            ['type_key' => 'infant', 'count' => 1, 'base_per_pax' => 20.0, 'tax_per_pax' => 0.0],
        ];

        $this->assertFalse(FlightPassengerFareLinesPresenter::linesMatchExpectedTotal($lines, 1495.0));
    }

    public function test_needs_refresh_when_lines_cover_all_types_but_totals_differ(): void
    {
        $booking = new B2bFlightBooking([
            'provider' => 'travelport',
            'total_amount' => 1495.0,
            'adults' => 1,
            'children' => 2,
            'infants' => 1,
            'itinerary_data' => [
                'passenger_fare_lines' => [
                    ['type_key' => 'adult', 'count' => 1, 'base_per_pax' => 200.0, 'tax_per_pax' => 325.0],
                    ['type_key' => 'child', 'count' => 2, 'base_per_pax' => 100.0, 'tax_per_pax' => 162.5],
                    ['type_key' => 'infant', 'count' => 1, 'base_per_pax' => 20.0, 'tax_per_pax' => 0.0],
                ],
            ],
        ]);

        $this->assertTrue(FlightPassengerFareLinesPresenter::needsFareBreakdownRefresh($booking));
    }
}
