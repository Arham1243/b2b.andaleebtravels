<?php

namespace Tests\Unit\Services;

use App\Models\B2bFlightBooking;
use App\Services\FlightEticketPdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class FlightEticketPdfTest extends TestCase
{
    public function test_download_aborts_when_ticket_numbers_are_missing(): void
    {
        $booking = new B2bFlightBooking([
            'ticket_status' => 'issued',
            'ticket_numbers' => [],
        ]);

        try {
            FlightEticketPdf::download($booking, ['tickets' => []]);
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $e) {
            $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $e->getStatusCode());
        }
    }

    public function test_download_returns_pdf_response_for_valid_payload(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for PDF417 rendering.');
        }

        $booking = new B2bFlightBooking([
            'booking_number' => 'B2BFB202606080001',
            'sabre_record_locator' => '23PL3M',
            'ticket_status' => 'issued',
            'ticket_numbers' => ['1761234567890'],
            'departure_date' => now()->addDays(2),
            'created_at' => now(),
            'passengers_data' => [
                'passengers' => [
                    ['title' => 'Mr', 'first_name' => 'JOHN', 'last_name' => 'DOE', 'type' => 'ADT'],
                ],
            ],
            'itinerary_data' => [
                'legs' => [[
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
                        'cabin_code' => 'Economy',
                        'booking_code' => 'Y',
                    ]],
                ]],
            ],
        ]);

        $ticketDetails = [
            'source' => 'saved',
            'error' => null,
            'tickets' => [[
                'ticket_number' => '1761234567890',
                'passenger_name' => 'JOHN DOE',
            ]],
        ];

        $response = FlightEticketPdf::download($booking, $ticketDetails, 'combined', false);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('1761234567890.pdf', (string) $response->headers->get('Content-Disposition'));
    }
}
