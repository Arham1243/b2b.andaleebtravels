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
    $seg0 = $card['legs'][0]['segments'][0] ?? [];
    if (($seg0['carrier'] ?? '') !== 'WY') {
        continue;
    }

    foreach ($card['legs'][0]['segments'] ?? [] as $i => $seg) {
        echo "Seg $i: {$seg['carrier']}{$seg['flight_number']} {$seg['from']}->{$seg['to']} ";
        echo "dep={$seg['departure_clock']} arr={$seg['arrival_clock']} elapsed={$seg['elapsedTime']}\n";
        echo "  operating={$seg['operating_carrier']}\n";
        echo "  dep_raw={$seg['departure_time']}\n";
        echo "  arr_raw={$seg['arrival_time']}\n";
    }
    break;
}
