<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$search = [
    'trip_type' => 'one_way', 'from' => 'DXB', 'to' => 'KHI', 'departure_date' => '2026-06-18',
    'return_date' => null, 'adults' => 1, 'children' => 0, 'infants' => 0,
    'direct_flight' => false, 'nearby_airports' => false, 'student_fare' => false,
    'onward_cabin_class' => 'Economy',
];

$result = (new App\Services\FlightProviders\TravelportFlightProvider())->search($search);
$cards = $result->results;

foreach ($cards as $card) {
    $seg = data_get($card, 'legs.0.segments.0');
    if (!is_array($seg) || ($seg['carrier'] ?? '') !== 'GF' || ($seg['flight_number'] ?? '') !== '505') {
        continue;
    }
    echo 'GF505 @ ' . ($seg['departure_clock'] ?? '') . ' — ' . count($card['fare_options'] ?? []) . " fares\n";
    foreach ($card['fare_options'] ?? [] as $i => $fare) {
        $basis = flightFareBasisListingLabel($fare['fare_basis'] ?? '', $fare['fare_tags'] ?? []);
        echo "  {$i}: " . implode(',', $fare['fare_tags'] ?? []) .
            ' | ' . ($fare['fare_brand'] ?? '') .
            ' | ' . $basis .
            ' | ' . ($fare['totalPrice'] ?? '') . "\n";
    }
}

echo PHP_EOL . 'Total cards: ' . count($cards) . PHP_EOL;
$fareTotal = array_sum(array_map(fn ($c) => count($c['fare_options'] ?? []), $cards));
echo 'Total fare options: ' . $fareTotal . PHP_EOL;
