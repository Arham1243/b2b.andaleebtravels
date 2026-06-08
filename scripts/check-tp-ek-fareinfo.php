<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$raw = (new App\Services\Travelport\TravelportApiClient())->lowFareSearch([
    'trip_type' => 'one_way', 'from' => 'DXB', 'to' => 'KHI',
    'departure_date' => '2026-06-18', 'adults' => 1, 'onward_cabin_class' => 'Economy',
])['parsed'] ?? null;

$rsp = data_get($raw, 'Body.LowFareSearchRsp');
echo "Target branch: P7250866\n";
echo "Endpoint: emea.universal-api.pp.travelport.com (preprod)\n\n";

foreach (data_get($rsp, 'FareInfoList.FareInfo', []) as $f) {
    $a = $f['@attributes'] ?? $f;
    $fb = $a['FareBasis'] ?? '';
    if (($a['Origin'] ?? '') === 'DXB' && ($a['Destination'] ?? '') === 'KHI' && str_contains($fb, 'AE1')) {
        $bid = data_get($f, 'Brand.@attributes.BrandID') ?? '';
        echo "FareInfo: {$fb}\n";
    }
}

echo "\nAll EK BrandList names:\n";
foreach (data_get($rsp, 'BrandList.Brand', []) as $b) {
    $a = $b['@attributes'] ?? $b;
    if (($a['Carrier'] ?? '') === 'EK') {
        echo '  ' . ($a['Name'] ?? '') . ' (id=' . ($a['BrandID'] ?? '') . ")\n";
    }
}
