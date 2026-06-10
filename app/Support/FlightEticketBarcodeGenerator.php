<?php

namespace App\Support;

use Bcbp\Encoder;
use DateTimeImmutable;
use Le\PDF417\PDF417;
use Le\PDF417\Renderer\ImageRenderer;

final class FlightEticketBarcodeGenerator
{
    /**
     * @param  array<string, mixed>  $segment
     */
    public static function pngBase64(
        string $passengerName,
        string $pnr,
        array $segment,
        ?string $departureDate = null,
    ): ?string {
        $bcbp = self::bcbpString($passengerName, $pnr, $segment, $departureDate);
        if ($bcbp === null) {
            return null;
        }

        $pdf417 = new PDF417();
        $barcode = $pdf417->encode($bcbp);
        $renderer = new ImageRenderer(['format' => 'png', 'scale' => 2]);
        $png = $renderer->render($barcode);

        if ($png === '' || $png === false) {
            return null;
        }

        return base64_encode($png);
    }

    /**
     * @param  array<string, mixed>  $segment
     */
    public static function bcbpString(
        string $passengerName,
        string $pnr,
        array $segment,
        ?string $departureDate = null,
    ): ?string {
        $passengerName = self::formatPassengerName($passengerName);
        $pnr = strtoupper(trim($pnr));
        $from = strtoupper(trim((string) ($segment['from'] ?? '')));
        $to = strtoupper(trim((string) ($segment['to'] ?? '')));
        $carrier = strtoupper(trim((string) ($segment['carrier'] ?? '')));
        $flightNumber = preg_replace('/\D+/', '', (string) ($segment['flight_number'] ?? '')) ?: '0000';

        if ($passengerName === '' || $pnr === '' || $from === '' || $to === '' || $carrier === '') {
            return null;
        }

        $flightDate = self::resolveFlightDate($segment, $departureDate);
        if ($flightDate === null) {
            return null;
        }

        $bookingClass = strtoupper(substr(trim((string) (
            $segment['booking_code']
            ?? $segment['booking_class']
            ?? 'Y'
        )), 0, 1));

        return Encoder::encode([
            'data' => [
                'passengerName' => $passengerName,
                'legs' => [[
                    'operatingCarrierPNR' => substr($pnr, 0, 7),
                    'departureAirport' => substr($from, 0, 3),
                    'arrivalAirport' => substr($to, 0, 3),
                    'operatingCarrierDesignator' => substr($carrier, 0, 3),
                    'flightNumber' => str_pad(substr($flightNumber, 0, 4), 4, '0', STR_PAD_LEFT),
                    'flightDate' => $flightDate,
                    'compartmentCode' => $bookingClass !== '' ? $bookingClass : 'Y',
                    'seatNumber' => '',
                    'checkInSequenceNumber' => '',
                    'passengerStatus' => '1',
                ]],
            ],
        ]);
    }

    private static function formatPassengerName(string $name): string
    {
        $name = strtoupper(trim(preg_replace('/\s+/', ' ', $name) ?? ''));
        if ($name === '') {
            return '';
        }

        if (str_contains($name, '/')) {
            return $name;
        }

        $parts = explode(' ', $name);
        if (count($parts) === 1) {
            return $parts[0];
        }

        $last = array_pop($parts);
        $first = implode(' ', $parts);

        return $last . '/' . $first;
    }

    /**
     * @param  array<string, mixed>  $segment
     */
    private static function resolveFlightDate(array $segment, ?string $fallbackDate): ?DateTimeImmutable
    {
        $candidates = [
            $segment['departure_at'] ?? null,
            $segment['departure'] ?? null,
            $segment['departure_iso'] ?? null,
            $fallbackDate,
        ];

        foreach ($candidates as $candidate) {
            $text = trim((string) $candidate);
            if ($text === '') {
                continue;
            }

            try {
                return new DateTimeImmutable($text);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}
