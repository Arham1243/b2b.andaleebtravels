<?php

/**
 * Exercise Travelport airPrice → parse for a connecting itinerary (no hold/book).
 *
 * Usage: php scripts/test-travelport-hold.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\FlightProviders\TravelportFlightProvider;
use App\Services\Travelport\TravelportApiClient;
use App\Support\Travelport\TravelportAirPriceParser;
use App\Support\Travelport\TravelportHoldPayloadBuilder;

$searchData = [
    'trip_type' => 'one_way',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => date('Y-m-d', strtotime('+14 days')),
    'adults' => 1,
    'children' => 0,
    'infants' => 0,
    'onward_cabin_class' => 'Economy',
];

echo "Searching DXB → KHI ({$searchData['departure_date']})...\n";

$provider = new TravelportFlightProvider();
$result = $provider->search($searchData);

if (! ($result->success ?? false)) {
    echo 'Search failed: ' . ($result->error ?? 'unknown') . "\n";
    exit(1);
}

$card = null;
foreach ($result->results as $candidate) {
    if (strtolower((string) ($candidate['supplier'] ?? '')) !== 'travelport') {
        continue;
    }
    $segCount = count($candidate['legs'][0]['segments'] ?? []);
    if ($segCount >= 1) {
        $card = $candidate;
        echo "Selected card with {$segCount} segment(s), price {$candidate['totalPrice']} {$candidate['currency']}\n";
        break;
    }
}

if ($card === null) {
    echo "No Travelport card found.\n";
    exit(1);
}

$segments = TravelportHoldPayloadBuilder::buildAirPriceSegments($card);
echo 'airPrice segments: ' . count($segments) . "\n";

$client = new TravelportApiClient();
$counts = TravelportHoldPayloadBuilder::passengerCounts($searchData);
$priceResponse = $client->airPrice($segments, $counts);

if (! ($priceResponse['success'] ?? false)) {
    echo 'airPrice failed: ' . ($priceResponse['error'] ?? 'unknown') . "\n";
    echo substr((string) ($priceResponse['raw'] ?? ''), 0, 500) . "\n";
    exit(1);
}

$bookingCode = strtoupper((string) ($card['booking_code'] ?? ''));
$pricing = TravelportAirPriceParser::extract((string) ($priceResponse['raw'] ?? ''), $bookingCode);

// Mirror booking service alignment for accurate counts in dry-run output.
$segmentKeys = array_column($pricing['segments'] ?? [], 'key');
$alignedBookingInfos = [];
foreach ($pricing['booking_infos'] ?? [] as $bi) {
    $ref = (string) ($bi['segment_ref'] ?? '');
    if ($ref !== '' && in_array($ref, $segmentKeys, true) && ! isset($alignedBookingInfos[$ref])) {
        $alignedBookingInfos[$ref] = $bi;
    }
}
$pricing['booking_infos'] = array_values($alignedBookingInfos);

echo "Parsed solution: {$pricing['solution_key']}\n";
echo "Total: {$pricing['total_price']}\n";
echo "Segments for hold: " . count($pricing['segments'] ?? []) . "\n";
echo "Booking infos: " . count($pricing['booking_infos'] ?? []) . "\n";
echo "Fare infos: " . count($pricing['fare_infos'] ?? []) . "\n";
echo "Latest ticketing: " . ($pricing['latest_ticketing_time'] ?? 'n/a') . "\n";

if (($argv[1] ?? '') === 'hold') {
    $pricing['passenger_types'] = [['code' => 'ADT', 'traveler_ref' => 'traveler_1']];
    $travelers = [[
        'key' => 'traveler_1',
        'traveler_type' => 'ADT',
        'firstName' => 'Test',
        'lastName' => 'HoldUser',
        'dob' => '1990-01-15',
        'gender' => 'M',
        'phoneCountryCode' => '971',
        'phoneAreaCode' => '50',
        'phoneNumber' => '1234567',
        'email' => 'test@example.com',
    ]];

    echo "Creating sandbox hold...\n";
    $holdResponse = $client->airHold($travelers, $pricing);
    if (! ($holdResponse['success'] ?? false)) {
        echo 'airHold failed: ' . ($holdResponse['error'] ?? 'unknown') . "\n";
        echo substr((string) ($holdResponse['raw'] ?? ''), 0, 800) . "\n";
        exit(1);
    }

    $raw = (string) ($holdResponse['raw'] ?? '');
    preg_match('/UniversalRecord[^>]+LocatorCode="([^"]+)"/i', $raw, $uni);
    preg_match('/<(?:[\w-]+:)?AirReservation[^>]+LocatorCode="([^"]+)"/i', $raw, $air);
    echo 'Universal locator: ' . ($uni[1] ?? 'n/a') . "\n";
    echo 'Air reservation locator: ' . ($air[1] ?? 'n/a') . "\n";
    echo "OK — airHold succeeded.\n";
    exit(0);
}

echo "OK — airPrice parse ready for airHold.\n";
