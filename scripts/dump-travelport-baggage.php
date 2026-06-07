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
$fareInfos = data_get($rsp, 'FareInfoList.FareInfo');
$list = is_array($fareInfos) && array_is_list($fareInfos) ? $fareInfos : [$fareInfos];

foreach ($list as $fi) {
    $basis = $fi['@attributes']['FareBasis'] ?? '';
    if ($basis !== 'WDLIT3AE') {
        continue;
    }
    echo "FareInfo WDLIT3AE:\n";
    echo json_encode($fi, JSON_PRETTY_PRINT) . PHP_EOL;
}

$brands = data_get($rsp, 'BrandList.Brand');
$bl = is_array($brands) && array_is_list($brands) ? $brands : [$brands];
foreach ($bl as $b) {
    $name = $b['@attributes']['Name'] ?? '';
    if (stripos($name, 'LIGHT') !== false) {
        echo "\nBrand ECONOMY LIGHT:\n";
        echo json_encode($b, JSON_PRETTY_PRINT) . PHP_EOL;
    }
}

// Check for any carry/cabin keys in full response
$json = json_encode($rsp);
foreach (['CarryOn', 'carry', 'CabinBag', 'HandBag', 'OptionalService', 'Baggage'] as $needle) {
    if (stripos($json, $needle) !== false) {
        echo "Found needle: $needle\n";
    }
}
