<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cards = App\Support\Travelport\TravelportSearchPresenter::toResultCards(
    (new App\Services\Travelport\TravelportApiClient())->lowFareSearch([
        'trip_type' => 'one_way',
        'from' => 'DXB',
        'to' => 'KHI',
        'departure_date' => '2026-06-18',
        'adults' => 1,
        'onward_cabin_class' => 'Economy',
    ])['parsed'] ?? null,
    []
);

foreach ($cards as $card) {
    $s0 = data_get($card, 'legs.0.segments.0');
    if (($s0['carrier'] ?? '') !== 'EK') {
        continue;
    }
    if (count($card['legs'][0]['segments'] ?? []) !== 1) {
        continue;
    }
    echo ($s0['departure_clock'] ?? '') . ' fn=' . ($s0['flight_number'] ?? '') . ' opts=' . count($card['fare_options'] ?? []) . PHP_EOL;
    foreach ($card['fare_options'] ?? [] as $i => $f) {
        echo "  [{$i}] " . ($f['fare_brand'] ?? '') . ' | ' . ($f['fare_basis'] ?? '') . ' | ' . ($f['totalPrice'] ?? '') . ' | key=' . substr($f['travelport_price_point_key'] ?? '', -10) . PHP_EOL;
    }
}
