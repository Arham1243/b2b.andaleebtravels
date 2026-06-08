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

$rsp = data_get($raw, 'Body.LowFareSearchRsp');
$brandName = [];
foreach (data_get($rsp, 'BrandList.Brand', []) as $b) {
    $a = $b['@attributes'] ?? $b;
    $brandName[$a['BrandID'] ?? ''] = $a['Name'] ?? '';
}

$fareList = data_get($rsp, 'FareInfoList.FareInfo');
$fareList = is_array($fareList) && array_is_list($fareList) ? $fareList : [$fareList];

echo "=== All FareInfo DXB-KHI with brand ===\n";
foreach ($fareList as $f) {
    $a = $f['@attributes'] ?? $f;
    if (($a['Origin'] ?? '') !== 'DXB' || ($a['Destination'] ?? '') !== 'KHI') {
        continue;
    }
    $bid = data_get($f, 'Brand.@attributes.BrandID') ?? data_get($f, 'Brand.BrandID') ?? '';
    $upsell = data_get($f, 'Brand.UpsellBrand.@attributes.FareBasis') ?? data_get($f, 'Brand.UpsellBrand.FareBasis') ?? '';
    echo ($a['FareBasis'] ?? '') . ' | ' . ($brandName[$bid] ?? '-') . ' | upsell=' . $upsell . PHP_EOL;
}

$segments = data_get($rsp, 'AirSegmentList.AirSegment');
$segments = is_array($segments) && array_is_list($segments) ? $segments : [$segments];
$segByKey = [];
foreach ($segments as $s) {
    $a = $s['@attributes'] ?? $s;
    $segByKey[$a['Key'] ?? ''] = $a;
}

$fareByKey = [];
foreach ($fareList as $f) {
    $a = $f['@attributes'] ?? $f;
    $fareByKey[$a['Key'] ?? ''] = $a['FareBasis'] ?? '';
}

$pps = data_get($rsp, 'AirPricePointList.AirPricePoint');
$pps = is_array($pps) && array_is_list($pps) ? $pps : [$pps];

echo "\n=== EK nonstop price points (any flight#) ===\n";
foreach ($pps as $pp) {
    $ppA = $pp['@attributes'] ?? $pp;
    $pi = data_get($pp, 'AirPricingInfo');
    $pis = is_array($pi) && array_is_list($pi) ? $pi : [$pi];
    $opt = data_get($pis[0] ?? [], 'FlightOptionsList.FlightOption.0.Option.0');
    $bis = data_get($opt, 'BookingInfo');
    $bis = is_array($bis) && array_is_list($bis) ? $bis : [$bis];
    if (count($bis) !== 1) {
        continue;
    }
    $biA = $bis[0]['@attributes'] ?? $bis[0];
    $seg = $segByKey[$biA['SegmentRef'] ?? ''] ?? null;
    if (! $seg || ($seg['Carrier'] ?? '') !== 'EK') {
        continue;
    }
    if (($seg['Origin'] ?? '') !== 'DXB' || ($seg['Destination'] ?? '') !== 'KHI') {
        continue;
    }
    $fb = $fareByKey[$biA['FareInfoRef'] ?? ''] ?? '';
    echo ($ppA['TotalPrice'] ?? '') . ' | ' . ($seg['FlightNumber'] ?? '') . ' @ ' . substr($seg['DepartureTime'] ?? '', 11, 5) . ' | ' . $fb . PHP_EOL;
}

// Before groupCards - count cards from each PP for EK nonstop 0740
$cardsPreGroup = [];
foreach ($pps as $pp) {
    // simulate buildCard only - use presenter internal - skip, use count
}

echo "\nPresenter cards for EK nonstop:\n";
$cards = App\Support\Travelport\TravelportSearchPresenter::toResultCards($raw, []);
foreach ($cards as $card) {
    if (count($card['legs'][0]['segments'] ?? []) !== 1) {
        continue;
    }
    $s0 = $card['legs'][0]['segments'][0];
    if (($s0['carrier'] ?? '') !== 'EK') {
        continue;
    }
    echo ($s0['departure_clock'] ?? '') . ' fn=' . ($s0['flight_number'] ?? '') . ' opts=' . count($card['fare_options'] ?? []) . PHP_EOL;
}
