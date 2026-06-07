<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$client = new App\Services\Travelport\TravelportApiClient();
$raw = $client->lowFareSearch([
    'trip_type' => 'one_way',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => '2026-06-18',
    'adults' => 1,
    'onward_cabin_class' => 'Economy',
]);

$rsp = data_get($raw['parsed'], 'Body.LowFareSearchRsp');
echo 'Top-level keys: ' . implode(', ', array_keys($rsp)) . PHP_EOL;

$brands = data_get($rsp, 'BrandList.Brand');
if ($brands === null) {
    echo "No BrandList\n";
} else {
    $list = is_array($brands) && array_is_list($brands) ? $brands : [$brands];
    echo 'Brand count: ' . count($list) . PHP_EOL;
    foreach (array_slice($list, 0, 3) as $b) {
        $attrs = $b['@attributes'] ?? $b;
        echo '  Brand: ' . json_encode($attrs) . PHP_EOL;
    }
}

$pricePoints = data_get($rsp, 'AirPricePointList.AirPricePoint');
$list = is_array($pricePoints) && array_is_list($pricePoints) ? $pricePoints : [$pricePoints];
echo 'Price points: ' . count($list) . PHP_EOL;
