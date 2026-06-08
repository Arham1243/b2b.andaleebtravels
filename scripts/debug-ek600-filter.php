<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$searchData = [
    'trip_type' => 'one_way',
    'from' => 'DXB',
    'to' => 'KHI',
    'departure_date' => '2026-06-18',
    'adults' => 1,
    'onward_cabin_class' => 'Economy',
    'direct_flight' => 0,
];

$raw = (new App\Services\Travelport\TravelportApiClient())->lowFareSearch($searchData);
$cards = App\Support\Travelport\TravelportSearchPresenter::toResultCards($raw['parsed'] ?? null, $searchData);

echo "Before filter:\n";
foreach ($cards as $card) {
    $s0 = $card['legs'][0]['segments'][0] ?? [];
    if (($s0['carrier'] ?? '') !== 'EK' || ($s0['flight_number'] ?? '') !== '600') {
        continue;
    }
    foreach ($card['fare_options'] ?? [] as $i => $fo) {
        echo "  [$i] " . ($fo['fare_brand'] ?? '') . ' | ' . ($fo['fare_basis'] ?? '') . ' | ' . ($fo['totalPrice'] ?? '') . ' | cabin=' . ($fo['cabin_code'] ?? '') . ' | tags=' . json_encode($fo['fare_tags'] ?? []) . ' | match=' . (App\Support\FlightCabinPreference::fareMatchesSearch($fo, 'Economy', 'Economy', 'one_way') ? 'Y' : 'N') . PHP_EOL;
    }
}

$filtered = App\Support\FlightSearchResultFilter::apply($cards, $searchData);
echo "\nAfter filter:\n";
foreach ($filtered as $card) {
    $s0 = $card['legs'][0]['segments'][0] ?? [];
    if (($s0['carrier'] ?? '') !== 'EK' || ($s0['flight_number'] ?? '') !== '600') {
        continue;
    }
    echo 'opts=' . count($card['fare_options'] ?? []) . PHP_EOL;
    foreach ($card['fare_options'] ?? [] as $i => $fo) {
        echo "  [$i] " . ($fo['fare_brand'] ?? '') . ' | ' . ($fo['fare_basis'] ?? '') . PHP_EOL;
    }
}

// Try Sabre if configured
try {
    $mgr = app(App\Services\FlightProviders\FlightProviderManager::class);
    $merged = $mgr->search($searchData);
    echo "\nMerged providers EK600:\n";
    foreach ($merged['results'] ?? [] as $card) {
        $s0 = $card['legs'][0]['segments'][0] ?? [];
        if (($s0['carrier'] ?? '') !== 'EK' || ($s0['flight_number'] ?? '') !== '600') {
            continue;
        }
        echo 'supplier=' . ($card['supplier'] ?? '') . ' opts=' . count($card['fare_options'] ?? []) . PHP_EOL;
        foreach ($card['fare_options'] ?? [] as $fo) {
            echo '  - ' . ($fo['fare_brand'] ?? '') . ' | ' . ($fo['fare_basis'] ?? '') . ' | ' . ($fo['totalPrice'] ?? '') . PHP_EOL;
        }
    }
} catch (Throwable $e) {
    echo "\nMerged search error: " . $e->getMessage() . PHP_EOL;
}
