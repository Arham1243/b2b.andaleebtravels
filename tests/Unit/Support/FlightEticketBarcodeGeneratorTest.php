<?php

namespace Tests\Unit\Support;

use App\Support\FlightEticketBarcodeGenerator;
use PHPUnit\Framework\TestCase;

class FlightEticketBarcodeGeneratorTest extends TestCase
{
    public function test_generates_bcbp_string_for_segment(): void
    {
        $bcbp = FlightEticketBarcodeGenerator::bcbpString(
            'Mr. SALEM AWAD ALJABERI',
            '23PL3M',
            [
                'from' => 'AUH',
                'to' => 'SAW',
                'carrier' => 'PC',
                'flight_number' => '407',
                'booking_code' => 'Y',
            ],
            '2026-06-10',
        );

        $this->assertIsString($bcbp);
        $this->assertGreaterThan(40, strlen($bcbp));
    }

    public function test_generates_png_base64_for_segment(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for PDF417 rendering.');
        }

        $png = FlightEticketBarcodeGenerator::pngBase64(
            'ALJABERI/SALEM',
            '23PL3M',
            [
                'from' => 'AUH',
                'to' => 'SAW',
                'carrier' => 'PC',
                'flight_number' => '407',
                'booking_code' => 'Y',
            ],
            '2026-06-10',
        );

        $this->assertIsString($png);
        $this->assertNotSame('', $png);
        $this->assertNotFalse(base64_decode($png, true));
    }

    public function test_returns_null_when_required_segment_fields_missing(): void
    {
        $this->assertNull(FlightEticketBarcodeGenerator::bcbpString(
            'DOE/JOHN',
            'ABC123',
            ['from' => 'DXB'],
            '2026-06-10',
        ));
    }

    public function test_uses_segment_departure_datetime_when_booking_date_missing(): void
    {
        $bcbp = FlightEticketBarcodeGenerator::bcbpString(
            'KHAN/MUHAMMAD',
            '367AID',
            [
                'from' => 'KHI',
                'to' => 'DXB',
                'carrier' => 'EK',
                'flight_number' => '601',
                'booking_code' => 'L',
                'departure_datetime' => '2026-06-25T10:15:00+00:00',
            ],
            null,
        );

        $this->assertIsString($bcbp);
    }
}
