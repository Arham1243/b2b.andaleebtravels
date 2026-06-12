<?php

namespace Tests\Unit\Support\Travelport;

use App\Support\Travelport\TravelportHoldPayloadBuilder;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class TravelportHoldPayloadBuilderAgeTest extends TestCase
{
    public function test_child_age_is_computed_from_dob_at_travel_date(): void
    {
        $referenceDate = Carbon::parse('2026-06-12');

        $age = TravelportHoldPayloadBuilder::passengerAgeForTravelport(
            'CNN',
            '2020-06-09',
            $referenceDate,
        );

        $this->assertSame(6, $age);
    }

    public function test_child_traveler_payload_includes_age(): void
    {
        $travelers = TravelportHoldPayloadBuilder::buildTravelers([
            'lead' => ['phone' => '+971501234567', 'email' => 'test@example.com'],
            'passengers' => [
                ['type' => 'ADT', 'title' => 'Mr', 'first_name' => 'Adult', 'last_name' => 'One', 'dob' => '1990-01-01'],
                ['type' => 'CNN', 'title' => 'Mstr', 'first_name' => 'Child', 'last_name' => 'One', 'dob' => '2018-06-01'],
            ],
        ], [
            'adults' => 1,
            'children' => 1,
            'departure_date' => '2026-06-12',
        ]);

        $child = collect($travelers)->first(fn (array $row) => ($row['traveler_type'] ?? '') === 'CNN');

        $this->assertIsArray($child);
        $this->assertSame(8, $child['age'] ?? null);
    }
}
