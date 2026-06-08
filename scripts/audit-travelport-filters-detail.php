<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tp = new App\Services\FlightProviders\TravelportFlightProvider();

foreach (['Economy', 'Business'] as $cabin) {
    echo "\n--- Travelport {$cabin} ---\n";
    $out = $tp->search([
        'trip_type' => 'one_way',
        'from' => 'DXB',
        'to' => 'KHI',
        'departure_date' => '2026-06-18',
        'adults' => 1,
        'onward_cabin_class' => $cabin,
        'direct_flight' => false,
    ]);

    foreach ($out->results as $r) {
        $fare = $r['fare_options'][0] ?? [];
        $segs = count($r['legs'][0]['segments'] ?? []);
        echo ($r['validating_carrier'] ?? '?') . ' cabin=' . ($fare['cabin_code'] ?? 'n/a')
            . " segs={$segs} basis=" . ($fare['fare_basis'] ?? '') . PHP_EOL;
    }
}

echo "\n--- Travelport direct ---\n";
$out = $tp->search([
    'trip_type' => 'one_way',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => '2026-06-18',
    'adults' => 1,
    'onward_cabin_class' => 'Economy',
    'direct_flight' => true,
]);

foreach ($out->results as $r) {
    $segs = count($r['legs'][0]['segments'] ?? []);
    $route = implode('-', array_map(fn ($s) => ($s['from'] ?? '') . '>' . ($s['to'] ?? ''), $r['legs'][0]['segments'] ?? []));
    echo ($r['validating_carrier'] ?? '?') . " segs={$segs} route={$route}" . PHP_EOL;
}
