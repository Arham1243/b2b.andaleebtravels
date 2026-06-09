<?php

namespace Tests\Unit\Support\Travelport;

use App\Support\Travelport\TravelportAirTicketingResult;
use PHPUnit\Framework\TestCase;

class TravelportAirTicketingResultTest extends TestCase
{
    public function test_detects_ticket_failure_from_attributes(): void
    {
        $response = [
            'TicketFailureInfo' => [
                '@attributes' => [
                    'Code' => '12008',
                    'Message' => 'Host error during ticket issue. FORM OF PAYMENT REQUIRED',
                ],
            ],
        ];

        $this->assertTrue(TravelportAirTicketingResult::hasFailure($response));
        $this->assertStringContainsString('FORM OF PAYMENT REQUIRED', TravelportAirTicketingResult::failureMessage($response));
        $this->assertFalse(TravelportAirTicketingResult::isSuccessful($response, []));
    }

    public function test_detects_ticket_failure_from_raw_xml(): void
    {
        $response = [
            'raw' => '<air:TicketFailureInfo Code="12008" Message="Host error during ticket issue. FORM OF PAYMENT REQUIRED"/>',
        ];

        $this->assertTrue(TravelportAirTicketingResult::hasFailure($response));
    }

    public function test_success_when_ticket_numbers_present(): void
    {
        $response = [
            'ETR' => [
                '@attributes' => ['TicketNumber' => '1761234567890'],
            ],
        ];

        $this->assertTrue(TravelportAirTicketingResult::isSuccessful($response, ['1761234567890']));
    }
}
