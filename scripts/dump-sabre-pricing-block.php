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

$grouped = $result['rawResponse'] ?? [];
foreach ($grouped['itineraryGroups'] ?? [] as $groupRow) {
    foreach ($groupRow['itineraries'] ?? [] as $itinerary) {
        $legs = $itinerary['legs'] ?? [];
        // find EK602 via pricing + schedule - simplified: dump first EK pricing with LLEOPAE1
        foreach ($itinerary['pricingInformation'] ?? [] as $pi) {
            $fare = $pi['fare'] ?? [];
            $basis = data_get($fare, 'passengerInfoList.0.passengerInfo.fareComponents.0.fareBasisCode');
            if ($basis !== 'LLEOPAE1') {
                continue;
            }
            echo "=== pricingInformation keys ===\n";
            echo implode(', ', array_keys($pi)) . PHP_EOL;
            echo "pricingSource=" . ($pi['pricingSource'] ?? '') . PHP_EOL;
            echo "pricingSubsource=" . ($pi['pricingSubsource'] ?? '') . PHP_EOL;
            echo "offerSource=" . ($pi['offer']['source'] ?? data_get($pi, 'offer.source', '')) . PHP_EOL;
            echo json_encode($pi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            exit(0);
        }
    }
}

echo "LLEOPAE1 block not found\n";
