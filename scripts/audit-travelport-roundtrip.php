<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tp = new App\Services\FlightProviders\TravelportFlightProvider();
$out = $tp->search([
    'trip_type' => 'round_trip',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => '2026-06-18',
    'return_date' => '2026-06-25',
    'adults' => 1,
    'onward_cabin_class' => 'Economy',
    'direct_flight' => false,
]);

echo 'RT count: ' . count($out->results) . PHP_EOL;
foreach (array_slice($out->results, 0, 5) as $r) {
    $legs = $r['legs'] ?? [];
    echo ($r['validating_carrier'] ?? '?') . ' legs=' . count($legs);
    foreach ($legs as $i => $leg) {
        $segs = count($leg['segments'] ?? []);
        $from = $leg['segments'][0]['from'] ?? '?';
        $to = $leg['segments'][array_key_last($leg['segments'] ?? [])]['to'] ?? '?';
        echo " L{$i}:{$segs}seg {$from}>{$to}";
    }
    echo ' price=' . ($r['totalPrice'] ?? '') . PHP_EOL;
}
