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
$brands = data_get($rsp, 'BrandList.Brand');
$brands = is_array($brands) && array_is_list($brands) ? $brands : [$brands];
$brandNameById = [];
foreach ($brands as $b) {
    $a = $b['@attributes'] ?? $b;
    $brandNameById[$a['BrandID'] ?? ''] = $a['Name'] ?? '';
}

$segments = data_get($rsp, 'AirSegmentList.AirSegment');
$segments = is_array($segments) && array_is_list($segments) ? $segments : [$segments];
$segByKey = [];
foreach ($segments as $s) {
    $a = $s['@attributes'] ?? $s;
    $segByKey[$a['Key'] ?? ''] = $a;
}

$fareList = data_get($rsp, 'FareInfoList.FareInfo');
$fareList = is_array($fareList) && array_is_list($fareList) ? $fareList : [$fareList];
$fareByKey = [];
foreach ($fareList as $f) {
    $a = $f['@attributes'] ?? $f;
    $fareByKey[$a['Key'] ?? ''] = ['attrs' => $a, 'node' => $f];
}

$pps = data_get($rsp, 'AirPricePointList.AirPricePoint');
$pps = is_array($pps) && array_is_list($pps) ? $pps : [$pps];

echo "All EK direct DXB-KHI price points:\n";
foreach ($pps as $pp) {
    $ppA = $pp['@attributes'] ?? $pp;
    $pi = data_get($pp, 'AirPricingInfo');
    $pis = is_array($pi) && array_is_list($pi) ? $pi : [$pi];
    $pi0 = $pis[0] ?? [];
    $opt = data_get($pi0, 'FlightOptionsList.FlightOption.0.Option.0');
    if (! is_array($opt)) {
        continue;
    }
    $bis = data_get($opt, 'BookingInfo');
    $bis = is_array($bis) && array_is_list($bis) ? $bis : [$bis];

    $ekDirect = true;
    $flightLabel = '';
    foreach ($bis as $bi) {
        $biA = is_array($bi) ? ($bi['@attributes'] ?? $bi) : [];
        $seg = $segByKey[$biA['SegmentRef'] ?? ''] ?? null;
        if (! $seg) {
            $ekDirect = false;
            break;
        }
        $flightLabel .= ($seg['Carrier'] ?? '') . ($seg['FlightNumber'] ?? '') . ' ';
        if (($seg['Origin'] ?? '') !== 'DXB' && ($seg['Destination'] ?? '') !== 'KHI') {
            if (! (($seg['Origin'] ?? '') === 'DXB' || ($seg['Destination'] ?? '') === 'KHI')) {
                // connection
            }
        }
        if (count($bis) > 1) {
            $ekDirect = false;
        }
    }

    $bi0 = $bis[0] ?? [];
    $biA = is_array($bi0) ? ($bi0['@attributes'] ?? $bi0) : [];
    $seg = $segByKey[$biA['SegmentRef'] ?? ''] ?? null;
    if (! $seg || ($seg['Carrier'] ?? '') !== 'EK') {
        continue;
    }
    if (count($bis) !== 1) {
        continue;
    }

    $fir = $biA['FareInfoRef'] ?? '';
    $fb = $fareByKey[$fir]['attrs']['FareBasis'] ?? '';
    $brandId = data_get($fareByKey[$fir]['node'] ?? [], 'Brand.@attributes.BrandID')
        ?? data_get($fareByKey[$fir]['node'] ?? [], 'Brand.BrandID');
    echo sprintf(
        "%-10s %-12s %-15s %s %s\n",
        $ppA['TotalPrice'] ?? '',
        $fb,
        $brandNameById[$brandId] ?? '',
        trim($flightLabel),
        substr($ppA['Key'] ?? '', -8),
    );
}

// Sabre check
echo "\nSabre EK600 fares:\n";
try {
    $sabre = (new App\Services\FlightProviders\SabreFlightProvider())->search([
        'trip_type' => 'one_way',
        'from' => 'DXB',
        'to' => 'KHI',
        'departure_date' => '2026-06-18',
        'adults' => 1,
        'onward_cabin_class' => 'Economy',
        'direct_flight' => false,
    ]);
    foreach ($sabre->results as $card) {
        $s0 = data_get($card, 'legs.0.segments.0');
        if (($s0['carrier'] ?? '') !== 'EK' || ($s0['flight_number'] ?? '') !== '600') {
            continue;
        }
        echo 'dep=' . ($s0['departure_clock'] ?? '') . ' opts=' . count($card['fare_options'] ?? []) . ' supplier=' . ($card['supplier'] ?? '') . PHP_EOL;
        foreach ($card['fare_options'] ?? [] as $i => $f) {
            echo "  [{$i}] " . ($f['fare_brand'] ?? '') . ' | ' . ($f['fare_basis'] ?? '') . ' | ' . ($f['totalPrice'] ?? '') . PHP_EOL;
        }
    }
} catch (Throwable $e) {
    echo 'Sabre error: ' . $e->getMessage() . PHP_EOL;
}
