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

$response = (new App\Services\Travelport\TravelportApiClient())->lowFareSearch($search);
$parsed = $response['parsed'];
$rsp = data_get($parsed, 'Body.LowFareSearchRsp');

function asList(mixed $v): array {
    if ($v === null || $v === '') return [];
    if (!is_array($v)) return [$v];
    return array_is_list($v) ? $v : [$v];
}
function attr(mixed $n, string $name): mixed {
    if (!is_array($n)) return null;
    return data_get($n, '@attributes.'.$name) ?? data_get($n, $name);
}

$segmentsByKey = [];
foreach (asList(data_get($rsp, 'AirSegmentList.AirSegment')) as $seg) {
    if (!is_array($seg)) continue;
    $k = (string) attr($seg, 'Key');
    if ($k !== '') $segmentsByKey[$k] = $seg;
}
$brandsByKey = [];
foreach (asList(data_get($rsp, 'BrandList.Brand')) as $brand) {
    if (!is_array($brand)) continue;
    $k = (string) attr($brand, 'Key');
    if ($k !== '') $brandsByKey[$k] = $brand;
}
$fareInfosByKey = [];
foreach (asList(data_get($rsp, 'FareInfoList.FareInfo')) as $fi) {
    if (!is_array($fi)) continue;
    $k = (string) attr($fi, 'Key');
    if ($k !== '') $fareInfosByKey[$k] = $fi;
}

function resolveFareInfo(array $pi, array $fareInfosByKey): ?array {
    foreach (asList(data_get($pi, 'FareInfoRef')) as $ref) {
        if (!is_array($ref)) continue;
        $k = (string) attr($ref, 'Key');
        if ($k !== '' && isset($fareInfosByKey[$k])) return $fareInfosByKey[$k];
    }
    $single = data_get($pi, 'FareInfoRef');
    if (is_array($single)) {
        $k = (string) attr($single, 'Key');
        if ($k !== '' && isset($fareInfosByKey[$k])) return $fareInfosByKey[$k];
    }
    foreach (asList(data_get($pi, 'FareInfo')) as $fi) {
        if (is_array($fi)) return $fi;
    }
    return null;
}

function isNdc(?array $fareInfo, array $brandsByKey): bool {
    if ($fareInfo === null) return false;
    $brandNode = data_get($fareInfo, 'Brand');
    if (!is_array($brandNode)) return false;
    $brandKey = (string) attr($brandNode, 'Key');
    $brand = ($brandKey !== '' && isset($brandsByKey[$brandKey])) ? $brandsByKey[$brandKey] : null;
    return $brand !== null && strtolower((string) attr($brand, 'BrandedDetailsAvailable')) === 'true';
}

$counts = ['ndc' => 0, 'gds' => 0];
$rows = [];

foreach (asList(data_get($rsp, 'AirPricePointList.AirPricePoint')) as $pp) {
    if (!is_array($pp)) continue;
    $type = 'gds';
    $basis = '';
    $brand = '';
    $carrier = '';
    $flight = '';
    $dep = '';
    $price = (string) (attr($pp, 'TotalPrice') ?? attr($pp, 'ApproximateTotalPrice') ?? '');

    foreach (asList(data_get($pp, 'AirPricingInfo')) as $pi) {
        if (!is_array($pi)) continue;
        $fi = resolveFareInfo($pi, $fareInfosByKey);
        if ($fi) {
            $basis = (string) attr($fi, 'FareBasis');
            if (isNdc($fi, $brandsByKey)) $type = 'ndc';
            $brandNode = data_get($fi, 'Brand');
            if (is_array($brandNode)) {
                $bk = (string) attr($brandNode, 'Key');
                if ($bk !== '' && isset($brandsByKey[$bk])) {
                    $brand = (string) attr($brandsByKey[$bk], 'Name');
                }
            }
        }
        foreach (asList(data_get($pi, 'FlightOptionsList.FlightOption')) as $fo) {
            if (!is_array($fo)) continue;
            $opt = asList(data_get($fo, 'Option'))[0] ?? null;
            if (!is_array($opt)) continue;
            foreach (asList(data_get($opt, 'BookingInfo')) as $bi) {
                if (!is_array($bi)) continue;
                $seg = $segmentsByKey[(string) attr($bi, 'SegmentRef')] ?? null;
                if (!is_array($seg)) continue;
                $carrier = (string) attr($seg, 'Carrier');
                $flight = (string) attr($seg, 'FlightNumber');
                $depRaw = (string) attr($seg, 'DepartureTime');
                if (preg_match('/T(\d{2}:\d{2})/', $depRaw, $m)) $dep = $m[1];
            }
        }
    }

    $counts[$type]++;
    $rows[] = compact('carrier', 'flight', 'dep', 'type', 'brand', 'basis', 'price');
}

echo json_encode(['counts' => $counts, 'total' => count($rows)], JSON_PRETTY_PRINT) . PHP_EOL;
echo PHP_EOL;
foreach ($rows as $r) {
    echo implode(' | ', [$r['carrier'].$r['flight'], $r['dep'], strtoupper($r['type']), $r['brand'], $r['basis'], $r['price']]) . PHP_EOL;
}

$cards = App\Support\Travelport\TravelportSearchPresenter::toResultCards($parsed, $search);
$fareCount = 0;
foreach ($cards as $card) {
    $fareCount += count($card['fare_options'] ?? []);
}
echo PHP_EOL . "Grouped cards: " . count($cards) . ", total fare options: {$fareCount}" . PHP_EOL;
