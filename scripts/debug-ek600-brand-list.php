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

$resp = (new App\Services\Travelport\TravelportApiClient())->lowFareSearch($search, true, true);
$rsp = data_get($resp, 'parsed.Body.LowFareSearchRsp');

echo "=== All BrandList entries ===" . PHP_EOL;
foreach (data_get($rsp, 'BrandList.Brand', []) as $brand) {
    if (! is_array($brand)) continue;
    $attrs = $brand['@attributes'] ?? $brand;
    echo sprintf(
        "  Key=%s BrandID=%s Name=%s\n",
        $attrs['Key'] ?? '',
        $attrs['BrandID'] ?? '',
        $attrs['Name'] ?? '',
    );
}

echo PHP_EOL . "=== All EK600 AirPricePoints (upsell LFS) ===" . PHP_EOL;
// reuse dump from debug script - count all price points mentioning EK600

function attr($node, string $name): string {
    if (! is_array($node)) return '';
    return (string) ($node['@attributes'][$name] ?? $node[$name] ?? '');
}
function asList($v): array {
    if (! is_array($v)) return [];
    return array_is_list($v) ? $v : [$v];
}

$segments = [];
foreach (asList(data_get($rsp, 'AirSegmentList.AirSegment')) as $seg) {
    if (is_array($seg)) $segments[attr($seg, 'Key')] = $seg;
}

foreach (asList(data_get($rsp, 'AirPricePointList.AirPricePoint')) as $pp) {
    if (! is_array($pp)) continue;
    $ek600 = false;
    foreach (asList(data_get($pp, 'AirPricingInfo.FlightOptionsList.FlightOption')) as $fo) {
        foreach (asList(data_get($fo, 'Option')) as $opt) {
            foreach (asList(data_get($opt, 'BookingInfo')) as $bi) {
                $seg = $segments[attr($bi, 'SegmentRef')] ?? null;
                if ($seg && attr($seg, 'Carrier') === 'EK' && trim(attr($seg, 'FlightNumber')) === '600') {
                    $ek600 = true;
                }
            }
        }
    }
    if (! $ek600) continue;
    echo '  TotalPrice=' . attr($pp, 'TotalPrice') . ' Key=' . attr($pp, 'Key') . PHP_EOL;
}

echo PHP_EOL . "Search for FLEX PLUS in raw XML: ";
echo (stripos($resp['raw'] ?? '', 'FLEX PLUS') !== false || stripos($resp['raw'] ?? '', 'Flex Plus') !== false) ? 'FOUND' : 'NOT FOUND';
echo PHP_EOL;
