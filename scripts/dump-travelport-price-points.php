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

$rsp = data_get($raw['parsed'], 'Body.LowFareSearchRsp');

$segList = data_get($rsp, 'AirSegmentList.AirSegment');
$segList = is_array($segList) && array_is_list($segList) ? $segList : [$segList];
$segByKey = [];
foreach ($segList as $s) {
    if (! is_array($s)) {
        continue;
    }
    $k = $s['@attributes']['Key'] ?? $s['Key'] ?? '';
    if ($k !== '') {
        $segByKey[$k] = $s;
    }
}

$fareList = data_get($rsp, 'FareInfoList.FareInfo');
$fareList = is_array($fareList) && array_is_list($fareList) ? $fareList : [$fareList];
$fareByKey = [];
foreach ($fareList as $f) {
    if (! is_array($f)) {
        continue;
    }
    $k = $f['@attributes']['Key'] ?? $f['Key'] ?? '';
    if ($k !== '') {
        $fareByKey[$k] = $f;
    }
}

$pps = data_get($rsp, 'AirPricePointList.AirPricePoint');
$pps = is_array($pps) && array_is_list($pps) ? $pps : [$pps];

$byRoute = [];

foreach ($pps as $pp) {
    if (! is_array($pp)) {
        continue;
    }

    $price = $pp['@attributes']['TotalPrice'] ?? $pp['TotalPrice'] ?? '';
    $pi = $pp['AirPricingInfo'] ?? null;
    if (is_array($pi) && array_is_list($pi)) {
        $pi = $pi[0];
    }

    $fo = data_get($pi, 'FlightOptionsList.FlightOption');
    if (is_array($fo) && array_is_list($fo)) {
        $fo = $fo[0];
    }

    $opt = data_get($fo, 'Option');
    if (is_array($opt) && array_is_list($opt)) {
        $opt = $opt[0];
    }

    $bis = data_get($opt, 'BookingInfo');
    $bis = is_array($bis) && array_is_list($bis) ? $bis : [$bis];

    $route = [];
    $fareBasis = '';

    foreach ($bis as $bi) {
        if (! is_array($bi)) {
            continue;
        }

        $ref = $bi['@attributes']['SegmentRef'] ?? $bi['SegmentRef'] ?? '';
        $seg = $segByKey[$ref] ?? null;
        if ($seg !== null) {
            $a = $seg['@attributes'] ?? $seg;
            $route[] = ($a['Carrier'] ?? '') . ($a['FlightNumber'] ?? '') . '@' . substr((string) ($a['DepartureTime'] ?? ''), 11, 5);
        }

        $fir = $bi['@attributes']['FareInfoRef'] ?? $bi['FareInfoRef'] ?? '';
        $fareBasis = $fareByKey[$fir]['@attributes']['FareBasis'] ?? $fareByKey[$fir]['FareBasis'] ?? $fareBasis;
    }

    $routeKey = implode('|', $route);
    $byRoute[$routeKey][] = [
        'price' => $price,
        'fare_basis' => $fareBasis,
        'key' => $pp['@attributes']['Key'] ?? '',
    ];

    echo $price . '  ' . $routeKey . '  basis=' . $fareBasis . PHP_EOL;
}

echo PHP_EOL . '=== Routes with multiple price points ===' . PHP_EOL;
foreach ($byRoute as $route => $items) {
    if (count($items) > 1) {
        echo $route . ' (' . count($items) . ' fares)' . PHP_EOL;
        foreach ($items as $item) {
            echo '  ' . $item['price'] . '  ' . $item['fare_basis'] . PHP_EOL;
        }
    }
}
