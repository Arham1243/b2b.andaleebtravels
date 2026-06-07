<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rawDate = 'Jun 18, 2026';
$searchData = [
    'trip_type' => 'one_way',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => \Carbon\Carbon::parse($rawDate)->format('Y-m-d'),
    'return_date' => null,
    'adults' => 1,
    'children' => 0,
    'infants' => 0,
    'direct_flight' => false,
    'nearby_airports' => true,
    'student_fare' => false,
    'onward_cabin_class' => 'Economy',
    'return_cabin_class' => 'Economy',
];

$manager = new App\Services\FlightProviders\FlightProviderManager([
    new App\Services\FlightProviders\SabreFlightProvider(),
    new App\Services\FlightProviders\TravelportFlightProvider(),
]);

$out = $manager->search($searchData);
echo 'Merged results: ' . count($out['results']) . PHP_EOL;
echo 'Sabre: ' . count(array_filter($out['results'], fn ($r) => ($r['supplier'] ?? '') === 'sabre')) . PHP_EOL;
echo 'Travelport: ' . count(array_filter($out['results'], fn ($r) => ($r['supplier'] ?? '') === 'travelport')) . PHP_EOL;
foreach ($out['messages'] as $m) {
    if (in_array(strtolower($m['severity'] ?? ''), ['error', 'warning'], true)) {
        echo ($m['severity'] ?? '') . ': ' . ($m['text'] ?? '') . PHP_EOL;
    }
}
