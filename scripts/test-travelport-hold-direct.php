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
    'direct_flight' => true,
    'onward_cabin_class' => 'Economy',
];

$r = (new TravelportFlightProvider())->search($searchData);
$card = null;
foreach ($r->results as $c) {
    if (($c['supplier'] ?? '') !== 'travelport') {
        continue;
    }
    $segCount = count($c['legs'][0]['segments'] ?? []);
    if ($segCount === 1) {
        $card = $c;
        echo "Direct card: {$c['totalPrice']} {$c['currency']}\n";
        break;
    }
}

if ($card === null) {
    echo "No direct Travelport card\n";
    exit(1);
}

$segments = TravelportHoldPayloadBuilder::buildAirPriceSegments($card);
$client = new TravelportApiClient();
$price = $client->airPrice($segments, ['ADT' => 1, 'CNN' => 0, 'INF' => 0]);
$p = TravelportAirPriceParser::extract($price['raw'], strtoupper($card['booking_code'] ?? ''));
$p['passenger_types'] = [['code' => 'ADT', 'traveler_ref' => 'traveler_1']];
$travelers = [[
    'key' => 'traveler_1', 'traveler_type' => 'ADT', 'firstName' => 'John', 'lastName' => 'Smith',
    'dob' => '1985-06-15', 'gender' => 'M', 'phoneCountryCode' => '971', 'phoneAreaCode' => '50',
    'phoneNumber' => '1234567', 'email' => 'john.smith@example.com',
]];
$hold = $client->airHold($travelers, $p);
echo ($hold['success'] ?? false) ? "HOLD OK\n" : ('HOLD FAIL: ' . ($hold['error'] ?? '') . "\n");
