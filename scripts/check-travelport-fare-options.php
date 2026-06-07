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

foreach ($cards as $i => $card) {
    $seg0 = $card['legs'][0]['segments'][0] ?? [];
    if (($seg0['carrier'] ?? '') !== 'GF' || ($seg0['flight_number'] ?? '') !== '511') {
        continue;
    }

    echo "Card $i GF511\n";
    echo '  fare_options: ' . count($card['fare_options'] ?? []) . PHP_EOL;
    foreach ($card['fare_options'] ?? [] as $fi => $fare) {
        echo "    [$fi] " . ($fare['fare_brand'] ?? '') . '  ' . ($fare['totalPrice'] ?? '') . '  ' . ($fare['fare_basis'] ?? '') . PHP_EOL;
    }
}

echo PHP_EOL . 'All cards with multiple fares:' . PHP_EOL;
foreach ($cards as $i => $card) {
    $count = count($card['fare_options'] ?? []);
    if ($count <= 1) {
        continue;
    }

    $seg0 = $card['legs'][0]['segments'][0] ?? [];
    echo "Card $i: {$seg0['carrier']}{$seg0['flight_number']}  {$count} fares" . PHP_EOL;
}
