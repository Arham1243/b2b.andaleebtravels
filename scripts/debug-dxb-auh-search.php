<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$search = [
    'trip_type' => 'round_trip',
    'from' => 'DXB',
    'to' => 'AUH',
    'departure_date' => '2026-06-12',
    'return_date' => '2026-06-20',
    'adults' => 1,
    'children' => 0,
    'infants' => 0,
    'direct_flight' => false,
    'nearby_airports' => false,
    'onward_cabin_class' => 'Economy',
    'return_cabin_class' => 'Economy',
];

$manager = new App\Services\FlightProviders\FlightProviderManager([
    new App\Services\FlightProviders\SabreFlightProvider(),
    new App\Services\FlightProviders\TravelportFlightProvider(),
]);

$out = $manager->search($search);

echo 'results: ' . count($out['results']) . PHP_EOL;
foreach ($out['messages'] as $msg) {
    echo ($msg['severity'] ?? '') . ': ' . ($msg['text'] ?? '') . PHP_EOL;
}
