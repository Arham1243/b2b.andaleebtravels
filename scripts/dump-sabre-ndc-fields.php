<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$result = (new App\Services\FlightProviders\SabreFlightSearchService())->search([
    'trip_type' => 'one_way',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => '2026-06-18',
    'adults' => 1,
    'onward_cabin_class' => 'Economy',
]);

file_put_contents(__DIR__ . '/sabre-shop-sample.json', json_encode($result['rawResponse'], JSON_PRETTY_PRINT));

$found = 0;
foreach ($result['rawResponse']['itineraryGroups'] ?? [] as $groupRow) {
    foreach ($groupRow['itineraries'] ?? [] as $itinerary) {
        foreach ($itinerary['pricingInformation'] ?? [] as $pi) {
            if (($pi['pricingSubsource'] ?? '') !== 'MIP') {
                continue;
            }
            $found++;
            if ($found > 1) {
                break 3;
            }
            echo "pricingSource=" . ($pi['pricingSource'] ?? '') . PHP_EOL;
            echo "pricingSubsource=" . ($pi['pricingSubsource'] ?? '') . PHP_EOL;
            echo "top keys: " . implode(', ', array_keys($pi)) . PHP_EOL;
            $fare = $pi['fare'] ?? [];
            echo "fare keys: " . implode(', ', array_keys($fare)) . PHP_EOL;
            echo "brandFeatures=" . json_encode($fare['brandFeatures'] ?? null) . PHP_EOL;
            echo "offer=" . json_encode($pi['offer'] ?? null) . PHP_EOL;
        }
    }
}

// grep ndc in file
$json = file_get_contents(__DIR__ . '/sabre-shop-sample.json');
preg_match_all('/"[^"]*[Nn][Dd][Cc][^"]*"\s*:\s*"([^"]*)"/', $json, $m);
echo "\nNDC-related JSON fields (sample):\n";
foreach (array_unique($m[0] ?? []) as $line) {
    echo $line . PHP_EOL;
}
