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

function findPaths($data, $needle, $path = ''): void {
    if (is_array($data)) {
        foreach ($data as $k => $v) {
            $p = $path === '' ? (string) $k : $path . '.' . $k;
            if (is_string($k) && stripos($k, $needle) !== false) {
                echo $p . "\n";
            }
            if (is_string($v) && stripos($v, $needle) !== false && strlen($v) < 80) {
                echo "$p = $v\n";
            }
            findPaths($v, $needle, $p);
        }
    }
}

echo "=== Keys with Optional ===\n";
findPaths($rsp, 'Optional');

echo "\n=== Keys with Carry ===\n";
findPaths($rsp, 'Carry');

// Dump brand with BrandedDetailsAvailable for GF 515 price point
$pricePoints = data_get($rsp, 'AirPricePointList.AirPricePoint');
$list = is_array($pricePoints) && array_is_list($pricePoints) ? $pricePoints : [$pricePoints];
foreach ($list as $pp) {
    $price = $pp['@attributes']['TotalPrice'] ?? '';
    if ($price !== 'AED515') {
        continue;
    }
    echo "\n=== AED515 price point brand/services ===\n";
    $brand = data_get($rsp, 'BrandList.Brand');
    $brands = is_array($brand) && array_is_list($brand) ? $brand : [$brand];
    foreach ($brands as $b) {
        if (($b['@attributes']['BrandID'] ?? '') === '2004609') {
            file_put_contents(__DIR__ . '/travelport-brand-gf.json', json_encode($b, JSON_PRETTY_PRINT));
            echo "brand saved\n";
        }
    }
}
