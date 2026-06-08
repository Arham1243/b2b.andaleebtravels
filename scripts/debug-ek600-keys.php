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
])['parsed'] ?? null;

$brands = data_get($raw, 'Body.LowFareSearchRsp.BrandList.Brand');
$brands = is_array($brands) && array_is_list($brands) ? $brands : [$brands];

foreach ($brands as $b) {
    $a = $b['@attributes'] ?? $b;
    if (($a['Carrier'] ?? '') !== 'EK') {
        continue;
    }
    echo json_encode([
        'BrandID' => $a['BrandID'] ?? '',
        'Name' => $a['Name'] ?? '',
        'UpSellBrandID' => $a['UpSellBrandID'] ?? null,
        'BrandedDetailsAvailable' => $a['BrandedDetailsAvailable'] ?? null,
    ], JSON_PRETTY_PRINT) . PHP_EOL;
}

$pps = data_get($raw, 'Body.LowFareSearchRsp.AirPricePointList.AirPricePoint');
$pps = is_array($pps) && array_is_list($pps) ? $pps : [$pps];
echo "\nPrice point count before presenter: " . count($pps) . "\n";

$cards = App\Support\Travelport\TravelportSearchPresenter::toResultCards($raw, []);
$ek600 = null;
foreach ($cards as $card) {
    $s0 = data_get($card, 'legs.0.segments.0');
    if (($s0['carrier'] ?? '') === 'EK' && ($s0['flight_number'] ?? '') === '600' && count($card['legs'][0]['segments'] ?? []) === 1) {
        $ek600 = $card;
        break;
    }
}

if ($ek600) {
    foreach ($ek600['fare_options'] as $i => $f) {
        echo "Option {$i} key=" . ($f['travelport_price_point_key'] ?? '') . PHP_EOL;
    }
}

// Cards before groupCardsByRouting - need to test by calling buildCard path
// Count raw EK600 price points
$segments = data_get($raw, 'Body.LowFareSearchRsp.AirSegmentList.AirSegment');
$segments = is_array($segments) && array_is_list($segments) ? $segments : [$segments];
$segByKey = [];
foreach ($segments as $s) {
    $a = $s['@attributes'] ?? $s;
    $segByKey[$a['Key'] ?? ''] = $a;
}

$count = 0;
foreach ($pps as $pp) {
    $pi = data_get($pp, 'AirPricingInfo');
    $pis = is_array($pi) && array_is_list($pi) ? $pi : [$pi];
    $opt = data_get($pis[0] ?? [], 'FlightOptionsList.FlightOption.0.Option.0');
    $bis = data_get($opt, 'BookingInfo');
    $bis = is_array($bis) && array_is_list($bis) ? $bis : [$bis];
    if (count($bis) !== 1) {
        continue;
    }
    $biA = ($bis[0]['@attributes'] ?? $bis[0] ?? []);
    $seg = $segByKey[$biA['SegmentRef'] ?? ''] ?? null;
    if ($seg && ($seg['Carrier'] ?? '') === 'EK' && ($seg['FlightNumber'] ?? '') === '600') {
        $count++;
        echo 'PP ' . ($pp['@attributes']['Key'] ?? '') . ' price=' . ($pp['@attributes']['TotalPrice'] ?? '') . PHP_EOL;
    }
}
echo "Raw EK600 PP count: {$count}\n";
