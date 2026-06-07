<?php

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

$provider = new TravelportFlightProvider();
$result = $provider->search($searchData);
$client = new TravelportApiClient();

foreach ($result->results as $card) {
    if (strtolower((string) ($card['supplier'] ?? '')) !== 'travelport') {
        continue;
    }
    $segCount = count($card['legs'][0]['segments'] ?? []);
    if ($segCount !== 1) {
        continue;
    }

    echo "Non-stop: {$card['totalPrice']} {$card['currency']}\n";
    $segments = TravelportHoldPayloadBuilder::buildAirPriceSegments($card);
    $pr = $client->airPrice($segments, ['ADT' => 1, 'CNN' => 0, 'INF' => 0]);
    if (! ($pr['success'] ?? false)) {
        echo 'airPrice failed: ' . ($pr['error'] ?? '') . "\n";
        continue;
    }

    $pricing = TravelportAirPriceParser::extract($pr['raw'], strtoupper((string) ($card['booking_code'] ?? '')));
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

    $hold = $client->airHold($travelers, $pricing);
    if ($hold['success'] ?? false) {
        echo "HOLD OK\n";
        preg_match('/UniversalRecord[^>]+LocatorCode="([^"]+)"/i', (string) ($hold['raw'] ?? ''), $uni);
        echo 'Universal locator: ' . ($uni[1] ?? 'n/a') . "\n";
    } else {
        echo 'HOLD FAIL: ' . ($hold['error'] ?? '') . "\n";
    }
    exit(0);
}

echo "No non-stop Travelport card found.\n";
exit(1);
