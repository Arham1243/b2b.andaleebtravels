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

$stats = [];
foreach ($result['rawResponse']['itineraryGroups'] ?? [] as $groupRow) {
    foreach ($groupRow['itineraries'] ?? [] as $itinerary) {
        foreach ($itinerary['pricingInformation'] ?? [] as $pi) {
            $key = ($pi['pricingSubsource'] ?? '') . '|' . ($pi['distributionModel'] ?? '');
            $stats[$key] = ($stats[$key] ?? 0) + 1;
        }
    }
}
echo "Subsource|distributionModel counts:\n";
foreach ($stats as $k => $c) {
    echo "  {$k}: {$c}\n";
}

// EK602 specifics
foreach ($result['results'] ?? [] as $card) {
    $s0 = $card['legs'][0]['segments'][0] ?? [];
    if (($s0['carrier'] ?? '') !== 'EK' || trim($s0['flight_number'] ?? '') !== '602') {
        continue;
    }
    echo "\nEK602:\n";
    foreach ($card['fare_options'] ?? [] as $fo) {
        // need distributionModel - re-read from raw
    }
}

// Match by index
$cards = $result['results'];
foreach ($result['rawResponse']['itineraryGroups'] ?? [] as $groupRow) {
    foreach ($groupRow['itineraries'] ?? [] as $itinerary) {
        $refs = $itinerary['legs'] ?? [];
        // skip flight detection - print EK branded with distributionModel
        foreach ($itinerary['pricingInformation'] ?? [] as $pi) {
            $brand = data_get($pi, 'fare.passengerInfoList.0.passengerInfo.fareComponents.0.brandName')
                ?? data_get($pi, 'fare.passengerInfoList.0.passengerInfo.fareComponents.0.brand.code');
            $basis = data_get($pi, 'fare.passengerInfoList.0.passengerInfo.fareComponents.0.fareBasisCode');
            $carrier = data_get($pi, 'fare.validatingCarrierCode');
            if ($carrier !== 'EK') {
                continue;
            }
            if (! in_array($basis, ['LLEOPAE1', 'KLSOSAE1', 'XLOWFAE1'], true)) {
                continue;
            }
            echo "{$basis} | brand={$brand} | sub=" . ($pi['pricingSubsource'] ?? '') . ' | dist=' . ($pi['distributionModel'] ?? '') . PHP_EOL;
        }
    }
}
