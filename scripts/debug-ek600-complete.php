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
$brandNames = [];
foreach (data_get($rsp, 'BrandList.Brand', []) as $b) {
    $a = $b['@attributes'] ?? $b;
    $brandNames[$a['BrandID'] ?? ''] = $a['Name'] ?? '';
}

$segByKey = [];
foreach (data_get($rsp, 'AirSegmentList.AirSegment', []) as $s) {
    if (! is_array($s)) {
        continue;
    }
    $a = $s['@attributes'] ?? $s;
    $segByKey[$a['Key'] ?? ''] = $a;
}

$fareByKey = [];
foreach (data_get($rsp, 'FareInfoList.FareInfo', []) as $f) {
    if (! is_array($f)) {
        continue;
    }
    $a = $f['@attributes'] ?? $f;
    $fareByKey[$a['Key'] ?? ''] = $f;
}

$pps = data_get($rsp, 'AirPricePointList.AirPricePoint');
$pps = is_array($pps) && array_is_list($pps) ? $pps : [$pps];

echo "=== Every price point on EK600@0740 ===\n";
foreach ($pps as $pp) {
    $ppA = $pp['@attributes'] ?? $pp;
    $pi = data_get($pp, 'AirPricingInfo');
    if (is_array($pi) && array_is_list($pi)) {
        $pi = $pi[0];
    }
    $opt = data_get($pi, 'FlightOptionsList.FlightOption.0.Option.0');
    $bis = data_get($opt, 'BookingInfo');
    $bis = is_array($bis) && array_is_list($bis) ? $bis : [$bis];

    $route = [];
    $fareInfo = null;
    foreach ($bis as $bi) {
        if (! is_array($bi)) {
            continue;
        }
        $bia = $bi['@attributes'] ?? $bi;
        $seg = $segByKey[$bia['SegmentRef'] ?? ''] ?? null;
        if ($seg) {
            $sa = $seg['@attributes'] ?? $seg;
            $route[] = ($sa['Carrier'] ?? '') . ($sa['FlightNumber'] ?? '') . '@' . substr($sa['DepartureTime'] ?? '', 11, 5);
        }
        $fareInfo = $fareByKey[$bia['FareInfoRef'] ?? ''] ?? $fareInfo;
    }

    if ($route !== ['EK600@07:40']) {
        continue;
    }

    $fa = $fareInfo['@attributes'] ?? [];
    $bid = data_get($fareInfo, 'Brand.@attributes.BrandID') ?? '';
    echo ($ppA['TotalPrice'] ?? '') . ' | ' . ($fa['FareBasis'] ?? '') . ' | ' . ($brandNames[$bid] ?? '-') . ' | booking=' . ($bis[0]['@attributes']['BookingCode'] ?? '') . ' | ppKey=' . substr($ppA['Key'] ?? '', 0, 20) . PHP_EOL;
}

// Cards before grouping
$preGroup = [];
foreach ($pps as $pp) {
    $card = (new ReflectionMethod(App\Support\Travelport\TravelportSearchPresenter::class, 'buildCard'));
    // can't call private easily - use count from presenter with debug
}

$cards = App\Support\Travelport\TravelportSearchPresenter::toResultCards($raw, []);
foreach ($cards as $card) {
    $s0 = $card['legs'][0]['segments'][0] ?? [];
    if (($s0['carrier'] ?? '') !== 'EK' || ($s0['flight_number'] ?? '') !== '600') {
        continue;
    }
    echo "\nGrouped card fare_options:\n";
    foreach ($card['fare_options'] as $fo) {
        echo '  ' . ($fo['fare_brand'] ?? '') . ' | ' . ($fo['fare_basis'] ?? '') . ' | tags=' . json_encode($fo['fare_tags'] ?? []) . PHP_EOL;
    }
}

// git checkout test - simulate dedupe if basis had /NDC in key (bug hypothesis)
echo "\nDedupe simulation if basis included /NDC:\n";
$opts = [];
foreach ($cards as $card) {
    $s0 = $card['legs'][0]['segments'][0] ?? [];
    if (($s0['carrier'] ?? '') !== 'EK' || ($s0['flight_number'] ?? '') !== '600') {
        continue;
    }
    $opts = $card['fare_options'];
}
$seen = [];
foreach ($opts as $option) {
    $basisLabel = App\Helpers\Helper::flightFareBasisListingLabel($option['fare_basis'] ?? '', $option['fare_tags'] ?? []);
    $dedupeBad = implode('|', [$basisLabel, $option['fare_brand'] ?? '', $option['totalPrice'] ?? '']);
    $dedupeGood = implode('|', [$option['fare_basis'] ?? '', $option['fare_brand'] ?? '', $option['totalPrice'] ?? '']);
    echo "  basis={$option['fare_basis']} label={$basisLabel}\n";
}
