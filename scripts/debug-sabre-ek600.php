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
];

try {
    $svc = app(App\Services\FlightProviders\SabreFlightSearchService::class);
    $result = $svc->search($searchData);
    echo "Sabre cards: " . count($result['results'] ?? []) . PHP_EOL;
    foreach ($result['results'] ?? [] as $card) {
        $s0 = $card['legs'][0]['segments'][0] ?? [];
        if (($s0['carrier'] ?? '') !== 'EK') {
            continue;
        }
        $fn = $s0['flight_number'] ?? '';
        echo 'EK' . $fn . ' @ ' . ($s0['departure_clock'] ?? '') . ' opts=' . count($card['fare_options'] ?? []) . ' supplier=' . ($card['supplier'] ?? '') . PHP_EOL;
        foreach ($card['fare_options'] ?? [] as $fo) {
            echo '    ' . ($fo['fare_brand'] ?? '') . ' | ' . ($fo['fare_basis'] ?? '') . ' | ' . ($fo['totalPrice'] ?? '') . PHP_EOL;
        }
    }
} catch (Throwable $e) {
    echo 'Sabre error: ' . $e->getMessage() . PHP_EOL;
}
