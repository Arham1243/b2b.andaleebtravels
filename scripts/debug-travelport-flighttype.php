<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$client = new App\Services\Travelport\TravelportApiClient();

foreach ([false, true] as $direct) {
    $label = $direct ? 'direct' : 'all';
    $r = $client->lowFareSearch([
        'trip_type' => 'one_way',
        'from' => 'DXB',
        'to' => 'KHI',
        'departure_date' => '2026-06-18',
        'adults' => 1,
        'onward_cabin_class' => 'Economy',
        'direct_flight' => $direct,
    ]);

    echo "\n{$label}: success=" . (($r['success'] ?? false) ? 'yes' : 'no');
    echo ' error=' . ($r['error'] ?? 'none');
    $pps = data_get($r['parsed'], 'Body.LowFareSearchRsp.AirPricePointList.AirPricePoint');
    $count = is_array($pps) ? (array_is_list($pps) ? count($pps) : 1) : 0;
    echo " pricePoints={$count}\n";

    if (preg_match('/<common_v52_0:ResponseMessage[^>]*>(.*?)<\/common_v52_0:ResponseMessage>/s', (string) ($r['raw'] ?? ''), $m)) {
        echo 'message: ' . strip_tags($m[1]) . PHP_EOL;
    }
}
