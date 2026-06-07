<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$raw = (new App\Services\Travelport\TravelportApiClient())->lowFareSearch([
    'trip_type' => 'one_way',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => '2026-06-18',
    'adults' => 1,
    'onward_cabin_class' => 'Economy',
]);

$segs = data_get($raw['parsed'], 'Body.LowFareSearchRsp.AirSegmentList.AirSegment');
$segs = is_array($segs) && array_is_list($segs) ? $segs : [$segs];

foreach ($segs as $s) {
    $a = $s['@attributes'] ?? $s;
    if (($a['Carrier'] ?? '') !== 'QR' || ($a['FlightNumber'] ?? '') !== '1149') {
        continue;
    }

    echo json_encode($a, JSON_PRETTY_PRINT) . PHP_EOL;
}
