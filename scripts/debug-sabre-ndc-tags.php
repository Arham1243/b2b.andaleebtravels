<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$result = (new App\Services\FlightProviders\SabreFlightSearchService())->search([
    'trip_type' => 'one_way',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => '2026-06-18',
    'adults' => 1,
    'onward_cabin_class' => 'Economy',
]);

foreach ($result['results'] ?? [] as $card) {
    $s0 = $card['legs'][0]['segments'][0] ?? [];
    if (($s0['carrier'] ?? '') !== 'EK') {
        continue;
    }
    echo 'EK' . ($s0['flight_number'] ?? '') . " fare_options:\n";
    foreach ($card['fare_options'] ?? [] as $fo) {
        $label = flightFareBasisListingLabel($fo['fare_basis'] ?? '', $fo['fare_tags'] ?? []);
        echo '  ' . ($fo['fare_brand'] ?? '') . ' | ' . $label . ' | tags=' . json_encode($fo['fare_tags'] ?? []) . PHP_EOL;
    }
}
