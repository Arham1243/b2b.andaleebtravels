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
$brands = data_get($rsp, 'BrandList.Brand');
$brands = is_array($brands) && array_is_list($brands) ? $brands : [$brands];

echo "=== EK brands in response ===\n";
foreach ($brands as $b) {
    $a = $b['@attributes'] ?? $b;
    if (($a['Carrier'] ?? '') !== 'EK') {
        continue;
    }
    echo ($a['BrandID'] ?? '') . ' | ' . ($a['Name'] ?? '') . PHP_EOL;
}

$fareList = data_get($rsp, 'FareInfoList.FareInfo');
$fareList = is_array($fareList) && array_is_list($fareList) ? $fareList : [$fareList];

echo "\n=== FareInfo with Brand upsell chain (EK direct fares) ===\n";
foreach ($fareList as $f) {
    $a = $f['@attributes'] ?? $f;
    $fb = $a['FareBasis'] ?? '';
    if (! str_contains($fb, 'AE1') && ! str_contains($fb, 'OPAE')) {
        continue;
    }
    $bn = $f['Brand'] ?? null;
    if (! is_array($bn)) {
        echo "{$fb} - no brand\n";
        continue;
    }
    $ba = $bn['@attributes'] ?? $bn;
    $upsell = $bn['UpsellBrand'] ?? null;
    $upsellBasis = '';
    if (is_array($upsell)) {
        $ua = $upsell['@attributes'] ?? $upsell;
        $upsellBasis = $ua['FareBasis'] ?? '';
    }
    echo "{$fb} brandId=" . ($ba['BrandID'] ?? '') . ' upSell=' . $upsellBasis . PHP_EOL;
}

// All price points count
$pps = data_get($rsp, 'AirPricePointList.AirPricePoint');
$pps = is_array($pps) && array_is_list($pps) ? $pps : [$pps];
echo "\nTotal price points: " . count($pps) . PHP_EOL;

// Check if any fare basis looks like flex plus
foreach ($fareList as $f) {
    $a = $f['@attributes'] ?? $f;
    $fb = $a['FareBasis'] ?? '';
    if (preg_match('/FLEX|PLUS|FLX/i', $fb) || preg_match('/FLEX|PLUS|FLX/i', (string) data_get($f, 'Brand.@attributes.BrandID'))) {
        echo "flex plus candidate: {$fb}\n";
    }
}

foreach ($brands as $b) {
    $a = $b['@attributes'] ?? $b;
    if (($a['Carrier'] ?? '') === 'EK' && stripos($a['Name'] ?? '', 'FLEX') !== false) {
        echo 'brand: ' . ($a['Name'] ?? '') . ' id=' . ($a['BrandID'] ?? '') . PHP_EOL;
    }
}
