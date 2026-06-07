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

$client = new App\Services\Travelport\TravelportApiClient();
$r = $client->lowFareSearch($searchData);
$rsp = data_get($r['parsed'], 'Body.LowFareSearchRsp');
$seg = data_get($rsp, 'AirSegmentList.AirSegment');
$segFirst = is_array($seg) && array_is_list($seg) ? $seg[0] : $seg;
file_put_contents(__DIR__ . '/travelport-segment.json', json_encode($segFirst, JSON_PRETTY_PRINT));
echo 'segment written' . PHP_EOL;
