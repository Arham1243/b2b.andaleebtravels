<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$response = (new App\Services\Travelport\TravelportApiClient())->lowFareSearch([
    'trip_type' => 'one_way', 'from' => 'DXB', 'to' => 'KHI', 'departure_date' => '2026-06-18',
    'return_date' => null, 'adults' => 1, 'children' => 0, 'infants' => 0,
    'direct_flight' => false, 'nearby_airports' => false, 'student_fare' => false,
    'onward_cabin_class' => 'Economy',
]);

$parsed = $response['parsed'];
$cards = App\Support\Travelport\TravelportSearchPresenter::toResultCards($parsed, []);

foreach ($cards as $card) {
    $seg = data_get($card, 'legs.0.segments.0');
    if (!is_array($seg)) continue;
    if (($seg['carrier'] ?? '') !== 'GF' || ($seg['flight_number'] ?? '') !== '505') continue;
    foreach ($card['fare_options'] ?? [] as $i => $fare) {
        echo $i . ' key=' . ($fare['travelport_price_point_key'] ?? '') .
            ' tags=' . implode(',', $fare['fare_tags'] ?? []) .
            ' basis=' . ($fare['fare_basis'] ?? '') . PHP_EOL;
    }
}

// dump raw price points for GF505 routing
function asList(mixed $v): array {
    if ($v === null || $v === '') return [];
    if (!is_array($v)) return [$v];
    return array_is_list($v) ? $v : [$v];
}
function attr(mixed $n, string $name): mixed {
    if (!is_array($n)) return null;
    return data_get($n, '@attributes.'.$name) ?? data_get($n, $name);
}
$rsp = data_get($response['parsed'], 'Body.LowFareSearchRsp');
$segmentsByKey = [];
foreach (asList(data_get($rsp, 'AirSegmentList.AirSegment')) as $seg) {
    if (!is_array($seg)) continue;
    $k = (string) attr($seg, 'Key');
    if ($k !== '') $segmentsByKey[$k] = $seg;
}
echo PHP_EOL . 'RAW GF505 price points:' . PHP_EOL;
foreach (asList(data_get($rsp, 'AirPricePointList.AirPricePoint')) as $pp) {
    if (!is_array($pp)) continue;
    $match = false;
    foreach (asList(data_get($pp, 'AirPricingInfo')) as $pi) {
        foreach (asList(data_get($pi, 'FlightOptionsList.FlightOption')) as $fo) {
            $opt = asList(data_get($fo, 'Option'))[0] ?? null;
            foreach (asList(data_get($opt, 'BookingInfo')) as $bi) {
                $seg = $segmentsByKey[(string) attr($bi, 'SegmentRef')] ?? null;
                if (is_array($seg) && attr($seg, 'Carrier') === 'GF' && attr($seg, 'FlightNumber') === '505') {
                    $match = true;
                }
            }
        }
    }
    if (!$match) continue;
    echo 'key=' . attr($pp, 'Key') . ' price=' . (attr($pp, 'TotalPrice') ?? attr($pp, 'ApproximateTotalPrice')) . PHP_EOL;
}
