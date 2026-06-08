<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\FlightProviders\FlightProviderManager;
use App\Services\FlightProviders\SabreFlightProvider;
use App\Services\FlightProviders\TravelportFlightProvider;

$manager = new FlightProviderManager([
    new SabreFlightProvider(),
    new TravelportFlightProvider(),
]);

$out = $manager->search([
    'trip_type' => 'one_way',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => '2026-06-18',
    'adults' => 1,
    'onward_cabin_class' => 'Business',
    'direct_flight' => false,
]);

foreach ($out['results'] as $r) {
    if (($r['supplier'] ?? '') !== 'travelport') {
        continue;
    }
    $fare = $r['fare_options'][0] ?? [];
    echo ($r['validating_carrier'] ?? '?') . ' cabin=' . ($fare['cabin_code'] ?? '') . ' basis=' . ($fare['fare_basis'] ?? '') . PHP_EOL;
}

echo 'travelport count: ' . count(array_filter($out['results'], fn ($r) => ($r['supplier'] ?? '') === 'travelport')) . PHP_EOL;

$tpOnly = new TravelportFlightProvider();
$raw = $tpOnly->search([
    'trip_type' => 'one_way',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => '2026-06-18',
    'adults' => 1,
    'onward_cabin_class' => 'Business',
    'direct_flight' => false,
]);
echo 'raw travelport count: ' . count($raw->results) . PHP_EOL;
