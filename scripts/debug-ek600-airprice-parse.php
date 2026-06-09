<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Run fare_family air price and save parsed structure keys
$search = [
    'trip_type' => 'one_way', 'from' => 'DXB', 'to' => 'KHI', 'departure_date' => '2026-06-18',
    'return_date' => null, 'adults' => 1, 'children' => 0, 'infants' => 0,
    'direct_flight' => false, 'nearby_airports' => false, 'student_fare' => false,
    'onward_cabin_class' => 'Economy',
];

$lfs = (new App\Services\Travelport\TravelportApiClient())->lowFareSearch($search, true, true);
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
        'Key' => attr($seg, 'Key'),
        'Group' => attr($seg, 'Group', '0'),
        'ProviderCode' => attr($seg, 'ProviderCode') ?: '1G',
        'Carrier' => attr($seg, 'Carrier'),
        'FlightNumber' => attr($seg, 'FlightNumber'),
        'Origin' => attr($seg, 'Origin'),
        'Destination' => attr($seg, 'Destination'),
        'DepartureTime' => attr($seg, 'DepartureTime'),
        'ArrivalTime' => attr($seg, 'ArrivalTime'),
        'ClassOfService' => 'K',
        'Equipment' => attr($seg, 'Equipment', '777'),
    ]];
    break;
}

$client = new App\Services\Travelport\TravelportApiClient();
$result = $client->airPriceFareFamily($segments, ['ADT' => 1, 'CNN' => 0, 'INF' => 0]);

if (! ($result['success'] ?? false)) {
    echo 'Failed: ' . ($result['error'] ?? '') . PHP_EOL;
    exit(1);
}

$file = __DIR__ . '/ek600-airprice-parsed.json';
file_put_contents($file, json_encode($result['parsed'], JSON_PRETTY_PRINT));
echo "Saved {$file}\n";

$solutions = data_get($result, 'parsed.Body.AirPriceRsp.AirPriceResult.AirPricingSolution');
if ($solutions === null) {
    $solutions = data_get($result, 'parsed.Body.AirPriceRsp.AirItineraryPricingInfo');
}
echo 'Solutions path sample keys: ';
print_r(array_keys((array) $solutions));
