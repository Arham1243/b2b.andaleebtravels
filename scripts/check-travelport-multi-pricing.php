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
$pps = data_get($rsp, 'AirPricePointList.AirPricePoint');
$pps = is_array($pps) && array_is_list($pps) ? $pps : [$pps];

foreach ($pps as $pp) {
    $pi = $pp['AirPricingInfo'] ?? null;
    $count = is_array($pi) && array_is_list($pi) ? count($pi) : 1;
    if ($count > 1) {
        $price = $pp['@attributes']['TotalPrice'] ?? '';
        echo "Price point {$price} has {$count} AirPricingInfo entries\n";
    }
}

echo 'Done. Multi-pricing price points listed above if any.' . PHP_EOL;
