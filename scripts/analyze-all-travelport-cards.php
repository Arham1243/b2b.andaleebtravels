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
    $sig = implode('|', array_map(
        fn ($s) => ($s['carrier'] ?? '') . ($s['flight_number'] ?? '') . '@' . ($s['departure_clock'] ?? ''),
        $card['legs'][0]['segments'] ?? []
    ));
    echo sprintf(
        "%2d  AED%-7.0f  %s  arr=%s  dur=%s\n",
        $i,
        $card['totalPrice'],
        $sig,
        ($card['legs'][0]['segments'][array_key_last($card['legs'][0]['segments'] ?? [])]['arrival_clock'] ?? ''),
        $card['legs'][0]['elapsedTime'] ?? ''
    );
}
