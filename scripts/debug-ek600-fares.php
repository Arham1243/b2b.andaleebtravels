<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\FlightProviders\TravelportFlightProvider;
use App\Support\Travelport\TravelportSearchPresenter;

$search = [
    'trip_type' => 'one_way',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => '2026-06-18',
    'adults' => 1,
    'onward_cabin_class' => 'Economy',
    'direct_flight' => false,
];

$raw = (new App\Services\Travelport\TravelportApiClient())->lowFareSearch($search);
$rsp = data_get($raw['parsed'], 'Body.LowFareSearchRsp');
$brands = data_get($rsp, 'BrandList.Brand');
$brands = is_array($brands) && array_is_list($brands) ? $brands : [$brands];
$brandById = [];
foreach ($brands as $b) {
    $a = $b['@attributes'] ?? $b;
    $brandById[$a['BrandID'] ?? ''] = $a['Name'] ?? '';
}

$fareList = data_get($rsp, 'FareInfoList.FareInfo');
$fareList = is_array($fareList) && array_is_list($fareList) ? $fareList : [$fareList];
$fareByKey = [];
foreach ($fareList as $f) {
    $a = $f['@attributes'] ?? $f;
    $fareByKey[$a['Key'] ?? ''] = $a;
}

$pps = data_get($rsp, 'AirPricePointList.AirPricePoint');
$pps = is_array($pps) && array_is_list($pps) ? $pps : [$pps];

echo "=== Raw price points for EK 600 ===\n";
foreach ($pps as $pp) {
    $a = $pp['@attributes'] ?? $pp;
    $pricingInfos = $pp['AirPricingInfo'] ?? null;
    $pricingInfos = is_array($pricingInfos) && array_is_list($pricingInfos) ? $pricingInfos : [$pricingInfos];
    $pi = $pricingInfos[0] ?? [];
    $piA = is_array($pi) ? ($pi['@attributes'] ?? $pi) : [];

    $flightOptions = data_get($pi, 'FlightOptionsList.FlightOption');
    $flightOptions = is_array($flightOptions) && array_is_list($flightOptions) ? $flightOptions : [$flightOptions];
    $opt = data_get($flightOptions[0] ?? [], 'Option');
    $opts = is_array($opt) && array_is_list($opt) ? $opt : [$opt];
    $opt0 = $opts[0] ?? [];
    $bis = data_get($opt0, 'BookingInfo');
    $bis = is_array($bis) && array_is_list($bis) ? $bis : [$bis];
    $bi0 = $bis[0] ?? [];
    $biA = is_array($bi0) ? ($bi0['@attributes'] ?? $bi0) : [];

    $segRef = $biA['SegmentRef'] ?? '';
    $segments = data_get($rsp, 'AirSegmentList.AirSegment');
    $segments = is_array($segments) && array_is_list($segments) ? $segments : [$segments];
    $seg = null;
    foreach ($segments as $s) {
        $sa = $s['@attributes'] ?? $s;
        if (($sa['Key'] ?? '') === $segRef) {
            $seg = $sa;
            break;
        }
    }

    if (($seg['Carrier'] ?? '') !== 'EK' || ($seg['FlightNumber'] ?? '') !== '600') {
        continue;
    }

    $fir = $biA['FareInfoRef'] ?? '';
    $fb = $fareByKey[$fir]['FareBasis'] ?? '';
    $brandId = '';
    $fareNode = null;
    foreach ($fareList as $f) {
        $fa = $f['@attributes'] ?? $f;
        if (($fa['Key'] ?? '') === $fir) {
            $fareNode = $f;
            $bn = $f['Brand'] ?? null;
            if (is_array($bn)) {
                $brandId = ($bn['@attributes'] ?? $bn)['BrandID'] ?? '';
            }
            break;
        }
    }

    echo sprintf(
        "%s | %s | basis=%s | brand=%s | key=%s\n",
        $a['TotalPrice'] ?? '',
        ($seg['DepartureTime'] ?? '') . ' EK600',
        $fb,
        $brandById[$brandId] ?? $brandId,
        substr($a['Key'] ?? '', 0, 12),
    );
}

$cards = TravelportSearchPresenter::toResultCards($raw['parsed'], $search);
echo "\n=== Presenter cards EK 600 ===\n";
foreach ($cards as $card) {
    $seg0 = data_get($card, 'legs.0.segments.0');
    if (($seg0['carrier'] ?? '') !== 'EK' || ($seg0['flight_number'] ?? '') !== '600') {
        continue;
    }

    echo 'dep=' . ($seg0['departure_clock'] ?? '') . ' options=' . count($card['fare_options'] ?? []) . PHP_EOL;
    foreach ($card['fare_options'] ?? [] as $i => $fare) {
        echo "  [{$i}] " . ($fare['fare_brand'] ?? '') . ' | ' . ($fare['fare_basis'] ?? '') . ' | ' . ($fare['totalPrice'] ?? '') . PHP_EOL;
    }
}
