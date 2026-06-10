<?php

namespace Tests\Unit\Support;

use App\Models\B2bFlightBooking;
use App\Support\FlightEticketPresenter;
use Carbon\Carbon;
use Tests\TestCase;

class FlightEticketPresenterTest extends TestCase
{
    public function test_combined_payload_includes_all_travelers_and_respects_fare_flag(): void
    {
        $booking = $this->sampleBooking();
        $ticketDetails = $this->sampleTicketDetails();

        $withFare = FlightEticketPresenter::combined($booking, $ticketDetails, true);
        $withoutFare = FlightEticketPresenter::combined($booking, $ticketDetails, false);

        $this->assertNotNull($withFare);
        $this->assertTrue($withFare['include_fare']);
        $this->assertCount(2, $withFare['travelers']);
        $this->assertSame('1761234567890', $withFare['filename_ticket_number']);
        $this->assertNotEmpty($withFare['directions']);
        $firstSegment = $withFare['directions'][0]['segments'][0] ?? [];
        $this->assertSame('Non Stop , (05h:45m)', $firstSegment['stops_display'] ?? null);
        $this->assertSame('Abu Dhabi [AUH]', $firstSegment['from_code'] ?? null);
        $this->assertNotNull($withoutFare);
        $this->assertFalse($withoutFare['include_fare']);
    }

    public function test_separate_payload_returns_one_document_per_ticket(): void
    {
        $booking = $this->sampleBooking();
        $ticketDetails = $this->sampleTicketDetails();

        $payloads = FlightEticketPresenter::separate($booking, $ticketDetails, true);

        $this->assertCount(2, $payloads);
        $this->assertSame('1761234567890', $payloads[0]['filename_ticket_number']);
        $this->assertSame('1761234567891', $payloads[1]['filename_ticket_number']);
        $this->assertCount(1, $payloads[0]['travelers']);
        $this->assertCount(1, $payloads[1]['travelers']);
    }

    private function sampleBooking(): B2bFlightBooking
    {
        return new B2bFlightBooking([
            'booking_number' => 'B2BFB202606080001',
            'sabre_record_locator' => '23PL3M',
            'ticket_status' => 'issued',
            'departure_date' => Carbon::parse('2026-06-10'),
            'return_date' => Carbon::parse('2026-06-13'),
            'from_airport' => 'AUH',
            'to_airport' => 'SAW',
            'created_at' => Carbon::parse('2026-06-08 10:00:00'),
            'passengers_data' => [
                'passengers' => [
                    ['title' => 'Mr', 'first_name' => 'SALEM', 'last_name' => 'ALJABERI', 'type' => 'ADT'],
                    ['title' => 'Mrs', 'first_name' => 'SARA', 'last_name' => 'ALJABERI', 'type' => 'ADT'],
                ],
            ],
            'itinerary_data' => [
                'non_refundable' => true,
                'baggage_details' => [
                    'pax_table' => [
                        ['pax_type' => 'Adult', 'checked' => '20 KG', 'cabin' => '8 KG Cabin + 3 KG Under Seat'],
                    ],
                    'summary_items' => ['Adult - 20 KG checked', 'Adult 8 KG cabin'],
                ],
                'legs' => [
                    [
                        'elapsedTime' => 345,
                        'segments' => [[
                            'from' => 'AUH',
                            'to' => 'SAW',
                            'carrier' => 'PC',
                            'flight_number' => '407',
                            'departure_clock' => '05:00',
                            'arrival_clock' => '09:45',
                            'departure_display' => 'Wed, 10 Jun 26',
                            'arrival_display' => 'Wed, 10 Jun 26',
                            'departure_city' => 'Abu Dhabi',
                            'arrival_city' => 'Istanbul Sabiha',
                            'cabin_code' => 'Economy',
                            'booking_code' => 'Y',
                        ]],
                    ],
                    [
                        'elapsedTime' => 330,
                        'segments' => [[
                            'from' => 'SAW',
                            'to' => 'AUH',
                            'carrier' => 'PC',
                            'flight_number' => '406',
                            'departure_clock' => '21:25',
                            'arrival_clock' => '03:55',
                            'departure_display' => 'Sat, 13 Jun 26',
                            'arrival_display' => 'Sun, 14 Jun 26',
                            'departure_city' => 'Istanbul Sabiha',
                            'arrival_city' => 'Abu Dhabi',
                            'cabin_code' => 'Economy',
                            'booking_code' => 'Y',
                        ]],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return array{source: string, error: null, tickets: list<array<string, mixed>>}
     */
    private function sampleTicketDetails(): array
    {
        return [
            'source' => 'saved',
            'error' => null,
            'tickets' => [
                [
                    'ticket_number' => '1761234567890',
                    'passenger_name' => 'SALEM ALJABERI',
                    'total_price' => 'AED 1,250.00',
                    'base_price' => 'AED 900.00',
                    'taxes' => 'AED 350.00',
                    'fare_basis' => 'YLOWAE',
                    'refundable' => 'Non-refundable',
                ],
                [
                    'ticket_number' => '1761234567891',
                    'passenger_name' => 'SARA ALJABERI',
                    'total_price' => 'AED 1,250.00',
                    'base_price' => 'AED 900.00',
                    'taxes' => 'AED 350.00',
                    'fare_basis' => 'YLOWAE',
                    'refundable' => 'Non-refundable',
                ],
            ],
        ];
    }
}
