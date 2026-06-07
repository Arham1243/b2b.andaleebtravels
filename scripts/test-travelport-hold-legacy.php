<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

require __DIR__ . '/../libraries/TravelportAPI.php';

use App\Services\FlightProviders\TravelportFlightProvider;
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
    if (($c['supplier'] ?? '') === 'travelport' && count($c['legs'][0]['segments'] ?? []) === 1) {
        $card = $c;
        break;
    }
}

$segments = TravelportHoldPayloadBuilder::buildAirPriceSegments($card);
$legacy = new TravelportAPI();
$price = $legacy->airPrice($segments);
$pd = $legacy->extractPricingData($price['raw'], strtoupper($card['booking_code'] ?? ''));
$traveler = [
    'firstName' => 'John', 'lastName' => 'Smith', 'phoneNumber' => '1234567',
    'email' => 'john@example.com', 'dob' => '1985-06-15', 'gender' => 'M',
];
$hold = $legacy->airHold($traveler, $pd);
echo ($hold['success'] ?? false) ? "LEGACY HOLD OK\n" : ('LEGACY HOLD FAIL: ' . ($hold['error'] ?? '') . "\n");
