<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$searchData = [
    'trip_type' => 'one_way',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => '2026-06-18',
    'adults' => 1,
    'onward_cabin_class' => 'Economy',
];

$tp = new App\Services\FlightProviders\TravelportFlightProvider();
$r = $tp->search($searchData);

foreach ($r->results as $i => $card) {
    if ((float)($card['totalPrice'] ?? 0) !== 515.0) {
        continue;
    }
    echo "Card $i price 515\n";
    foreach ($card['legs'][0]['segments'] ?? [] as $si => $seg) {
        echo "  seg $si: {$seg['from']}->{$seg['to']} dep={$seg['departure_clock']} arr={$seg['arrival_clock']} elapsed={$seg['elapsedTime']}\n";
    }
    echo "  leg elapsed: " . ($card['legs'][0]['elapsedTime'] ?? '') . "\n";
    echo "  fare_options: " . count($card['fare_options'] ?? []) . "\n";
    echo "  fare_brand: " . ($card['fare_brand'] ?? '') . "\n";
    echo "  fare_basis: " . ($card['fare_options'][0]['fare_basis'] ?? '') . "\n";
    echo "  baggage: " . json_encode($card['baggage_details'] ?? []) . "\n";
    break;
}

$client = new App\Services\Travelport\TravelportApiClient();
$raw = $client->lowFareSearch($searchData);
$rsp = data_get($raw['parsed'], 'Body.LowFareSearchRsp');
file_put_contents(__DIR__ . '/travelport-fareinfo.json', json_encode(
    data_get($rsp, 'FareInfoList.FareInfo'),
    JSON_PRETTY_PRINT
));
echo "fareinfo written\n";
