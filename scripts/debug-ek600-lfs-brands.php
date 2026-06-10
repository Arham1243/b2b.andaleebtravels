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

function attr($node, string $name): string {
    if (! is_array($node)) return '';
    return (string) ($node['@attributes'][$name] ?? $node[$name] ?? '');
}
function asList($v): array {
    if (! is_array($v)) return [];
    return array_is_list($v) ? $v : [$v];
}

$client = new App\Services\Travelport\TravelportApiClient();
$resp = $client->lowFareSearch($search, true, true);
$rsp = data_get($resp, 'parsed.Body.LowFareSearchRsp');

echo "All brands in LFS upsell:\n";
foreach (asList(data_get($rsp, 'BrandList.Brand')) as $brand) {
    if (! is_array($brand)) continue;
    echo sprintf(
        "  id=%s name=%s BDA=%s tier=%s\n",
        attr($brand, 'BrandID'),
        attr($brand, 'Name'),
        attr($brand, 'BrandedDetailsAvailable'),
        attr($brand, 'BrandTier'),
    );
}
