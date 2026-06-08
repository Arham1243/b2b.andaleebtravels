<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sabre = new App\Services\FlightProviders\SabreFlightProvider();

foreach (['Economy', 'Business'] as $cabin) {
    echo "\n--- Sabre {$cabin} ---\n";
    $out = $sabre->search([
        'trip_type' => 'one_way',
        'from' => 'DXB',
        'to' => 'KHI',
        'departure_date' => '2026-06-18',
        'adults' => 1,
        'onward_cabin_class' => $cabin,
        'direct_flight' => false,
    ]);

    echo 'count: ' . count($out->results) . PHP_EOL;
    $cabins = [];
    foreach (array_slice($out->results, 0, 8) as $r) {
        $fare = $r['fare_options'][0] ?? [];
        $c = $fare['cabin_code'] ?? 'n/a';
        $cabins[$c] = ($cabins[$c] ?? 0) + 1;
        echo ($r['validating_carrier'] ?? '?') . " cabin={$c} basis=" . ($fare['fare_basis'] ?? '') . PHP_EOL;
    }
    echo 'sample cabin codes: ' . json_encode($cabins) . PHP_EOL;
}
