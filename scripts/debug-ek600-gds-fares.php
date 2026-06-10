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

$client = new App\Services\Travelport\TravelportApiClient();

function dumpEk600(array $parsed, array $search, string $label): void {
    $cards = App\Support\Travelport\TravelportSearchPresenter::toResultCards($parsed, $search);
    echo "=== {$label} ===" . PHP_EOL;
    foreach ($cards as $card) {
        $seg = data_get($card, 'legs.0.segments.0');
        if (! is_array($seg) || ($seg['carrier'] ?? '') !== 'EK' || trim((string) ($seg['flight_number'] ?? '')) !== '600') {
            continue;
        }
        foreach ($card['fare_options'] ?? [] as $f) {
            $tags = implode(',', $f['fare_tags'] ?? []);
            echo sprintf("  %s | %s | %s | tags=%s\n", $f['fare_brand'] ?? '', $f['fare_basis'] ?? '', $f['totalPrice'] ?? '', $tags);
        }
    }
    echo PHP_EOL;
}

foreach ([
    'gds_pub' => [false, false],
    'gds_branded' => [true, false],
    'gds_upsell' => [true, true],
] as $label => [$b, $u]) {
    $resp = $client->lowFareSearch($search, $b, $u);
    if (! ($resp['success'] ?? false)) {
        echo "=== {$label} FAILED ===\n\n";
        continue;
    }
    dumpEk600($resp['parsed'], $search, $label);
}

// AirPrice fare family - tag each solution
$lfs = $client->lowFareSearch($search, true, true);
$rsp = data_get($lfs, 'parsed.Body.LowFareSearchRsp');
function attr($node, string $name): string {
    if (! is_array($node)) return '';
    return (string) ($node['@attributes'][$name] ?? $node[$name] ?? '');
}
function asList($v): array {
    if (! is_array($v)) return [];
    return array_is_list($v) ? $v : [$v];
}

foreach (asList(data_get($rsp, 'AirSegmentList.AirSegment')) as $seg) {
    if (! is_array($seg) || attr($seg, 'Carrier') !== 'EK' || trim(attr($seg, 'FlightNumber')) !== '600') continue;
    $segments = [[
        'Key' => attr($seg, 'Key'), 'Group' => attr($seg, 'Group', '0'),
        'ProviderCode' => attr($seg, 'ProviderCode') ?: '1G',
        'Carrier' => attr($seg, 'Carrier'), 'FlightNumber' => attr($seg, 'FlightNumber'),
        'Origin' => attr($seg, 'Origin'), 'Destination' => attr($seg, 'Destination'),
        'DepartureTime' => attr($seg, 'DepartureTime'), 'ArrivalTime' => attr($seg, 'ArrivalTime'),
        'ClassOfService' => 'L', 'Equipment' => attr($seg, 'Equipment', '777'),
    ]];
    break;
}

$air = $client->airPriceFareFamily($segments, ['ADT' => 1, 'CNN' => 0, 'INF' => 0]);
echo "=== AirPrice FareFamily ===" . PHP_EOL;
$options = App\Support\Travelport\TravelportAirPricePresenter::toFareOptions($air['parsed'] ?? null, $search, []);
foreach ($options as $o) {
    echo sprintf("  %s | %s | %s | tags=%s\n", $o['fare_brand'] ?? '', $o['fare_basis'] ?? '', $o['totalPrice'] ?? '', implode(',', $o['fare_tags'] ?? []));
}

// Count gds vs ndc in raw air price solutions
$solutions = data_get($air, 'parsed.Body.AirPriceRsp.AirPriceResult.AirPricingSolution');
if ($solutions) {
    echo PHP_EOL . "Raw solution count: " . count(asList($solutions)) . PHP_EOL;
    foreach (asList($solutions) as $sol) {
        if (! is_array($sol)) continue;
        $pi = asList($sol['AirPricingInfo'] ?? null)[0] ?? null;
        $fi = is_array($pi) ? (asList($pi['FareInfo'] ?? null)[0] ?? null) : null;
        $brand = is_array($fi) && is_array($fi['Brand'] ?? null) ? attr($fi['Brand'], 'Name') : '';
        $bda = is_array($fi) && is_array($fi['Brand'] ?? null) ? attr($fi['Brand'], 'BrandedDetailsAvailable') : '';
        $basis = is_array($fi) ? attr($fi, 'FareBasis') : '';
        $price = attr($sol, 'TotalPrice');
        echo "  raw: {$brand} | {$basis} | {$price} | BDA={$bda}" . PHP_EOL;
    }
}

// Full loadMoreFares simulation
echo PHP_EOL . "=== loadMoreFares ===" . PHP_EOL;
$provider = new App\Services\FlightProviders\TravelportFlightProvider();
$initial = $provider->search($search);
foreach ($initial->results as $card) {
    $seg = data_get($card, 'legs.0.segments.0');
    if (! is_array($seg) || ($seg['carrier'] ?? '') !== 'EK' || trim((string) ($seg['flight_number'] ?? '')) !== '600') continue;
    $loaded = $provider->loadMoreFares($search, $card);
    foreach ($loaded['fare_options'] ?? [] as $f) {
        echo sprintf("  %s | %s | %s | tags=%s\n", $f['fare_brand'] ?? '', $f['fare_basis'] ?? '', $f['totalPrice'] ?? '', implode(',', $f['fare_tags'] ?? []));
    }
}
