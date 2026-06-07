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

$xml = $raw['raw'] ?? '';
if (preg_match_all('/<air:BaggageAllowance[^>]*>.*?<\/air:BaggageAllowance>/s', $xml, $m)) {
    echo 'BaggageAllowance blocks: ' . count($m[0]) . PHP_EOL;
    foreach (array_slice($m[0], 0, 5) as $block) {
        echo $block . PHP_EOL . "---\n";
    }
}

if (preg_match_all('/<air:FareInfo[^>]*FareBasis="WDLIT3AE"[^>]*>.*?<\/air:FareInfo>/s', $xml, $fm)) {
    echo "\nWDLIT3AE FareInfo XML:\n" . ($fm[0][0] ?? '') . PHP_EOL;
}

foreach (['CarryOn', 'CabinBag', 'HandBaggage', 'NumberOfPieces', 'MaxWeight'] as $term) {
    $count = substr_count($xml, $term);
    echo "$term count: $count\n";
}
