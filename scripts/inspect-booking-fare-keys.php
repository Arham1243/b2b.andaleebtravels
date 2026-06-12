<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\B2bFlightBooking;
use App\Support\Travelport\TravelportHoldPricingInfoParser;

$id = (int) ($argv[1] ?? 48);
$booking = B2bFlightBooking::find($id);

if (! $booking) {
    echo "Booking {$id} not found\n";
    exit(1);
}

$bookingRequest = is_array($booking->booking_request) ? $booking->booking_request : [];
$bookingResponse = is_array($booking->booking_response) ? $booking->booking_response : [];
$raw = (string) ($bookingResponse['raw'] ?? '');

echo 'PNR: ' . ($booking->sabre_record_locator ?? '') . PHP_EOL;
echo 'Universal: ' . $booking->travelportUniversalLocator() . PHP_EOL;
echo 'raw_len: ' . strlen($raw) . PHP_EOL;
echo 'hold_keys: ' . json_encode($bookingRequest['hold_air_pricing_info_keys'] ?? null) . PHP_EOL;
echo 'pricing_info_key: ' . ($bookingRequest['pricing_data']['pricing_info_key'] ?? '') . PHP_EOL;

$keys = TravelportHoldPricingInfoParser::extractKeys($bookingResponse);
echo 'extractKeys(booking_response): ' . json_encode($keys) . PHP_EOL;

$resolved = TravelportHoldPricingInfoParser::resolveKeysForTicketing($bookingRequest, $bookingResponse);
echo 'resolveKeysForTicketing: ' . json_encode($resolved) . PHP_EOL;

if ($raw !== '') {
    if (preg_match('/<(?:[\w-]+:)?AirReservation\b[\s\S]*?<\/(?:[\w-]+:)?AirReservation>/i', $raw, $m)) {
        preg_match_all('/<(?:[\w-]+:)?AirPricingInfo\b[^>]*\bKey="([^"]+)"/i', $m[0], $matches);
        echo 'AirPricingInfo in AirReservation block: ' . json_encode($matches[1] ?? []) . PHP_EOL;
        echo 'AirReservation snippet has StoredFare: ' . (str_contains($m[0], 'PricingType="StoredFare"') ? 'yes' : 'no') . PHP_EOL;
    } else {
        echo "No AirReservation block matched in raw\n";
    }

    echo 'Has AirSolutionChangedInfo: ' . (str_contains($raw, 'AirSolutionChangedInfo') ? 'yes' : 'no') . PHP_EOL;
}
