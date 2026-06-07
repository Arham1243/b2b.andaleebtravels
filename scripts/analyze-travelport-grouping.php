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

$gf505 = [];
foreach ($cards as $i => $card) {
    $seg0 = $card['legs'][0]['segments'][0] ?? [];
    if (($seg0['carrier'] ?? '') === 'GF' && ($seg0['flight_number'] ?? '') === '505') {
        $sig = implode('|', array_map(
            fn ($s) => ($s['carrier'] ?? '') . ($s['flight_number'] ?? '') . '@' . ($s['departure_clock'] ?? ''),
            $card['legs'][0]['segments'] ?? []
        ));
        $gf505[] = [
            'idx' => $i,
            'price' => $card['totalPrice'],
            'sig' => $sig,
            'fare_opts' => count($card['fare_options'] ?? []),
            'arr' => ($card['legs'][0]['segments'][array_key_last($card['legs'][0]['segments'] ?? [])]['arrival_clock'] ?? ''),
            'elapsed' => $card['legs'][0]['elapsedTime'] ?? '',
        ];
    }
}

echo json_encode($gf505, JSON_PRETTY_PRINT) . PHP_EOL;
