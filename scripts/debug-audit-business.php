<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$base = [
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => '2026-06-18',
    'adults' => 1,
    'children' => 0,
    'infants' => 0,
    'nearby_airports' => false,
    'student_fare' => false,
    'onward_cabin_class' => 'Economy',
    'return_cabin_class' => 'Economy',
];

$searchData = $base + ['trip_type' => 'one_way', 'direct_flight' => false, 'return_date' => null, 'onward_cabin_class' => 'Business'];

var_export($searchData['onward_cabin_class']);
echo PHP_EOL;

$manager = new App\Services\FlightProviders\FlightProviderManager([
    new App\Services\FlightProviders\SabreFlightProvider(),
    new App\Services\FlightProviders\TravelportFlightProvider(),
]);
$out = $manager->search($searchData);
echo 'total=' . count($out['results']) . ' tp=' . count(array_filter($out['results'], fn ($r) => ($r['supplier'] ?? '') === 'travelport')) . PHP_EOL;
