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

function attr($node, string $name, $default = ''): string {
    if (! is_array($node)) return (string) $default;
    if (isset($node['@attributes'][$name])) return (string) $node['@attributes'][$name];
    if (isset($node[$name])) return is_array($node[$name]) ? '' : (string) $node[$name];
    return (string) $default;
}

function asList($value): array {
    if (! is_array($value)) return [];
    if ($value === []) return [];
    if (array_is_list($value)) return $value;
    return [$value];
}

function ek600FaresFromResponse(?array $parsed, array $search): array {
    $cards = App\Support\Travelport\TravelportSearchPresenter::toResultCards($parsed, $search);
    foreach ($cards as $card) {
        $seg = data_get($card, 'legs.0.segments.0');
        if (is_array($seg) && ($seg['carrier'] ?? '') === 'EK' && trim((string) ($seg['flight_number'] ?? '')) === '600') {
            return $card['fare_options'] ?? [];
        }
    }
    return [];
}

function dumpRawEk600Brands(?array $parsed, string $label): void {
    echo "=== RAW brands/price points for EK600 — {$label} ===" . PHP_EOL;
    $rsp = data_get($parsed, 'Body.LowFareSearchRsp');
    if (! is_array($rsp)) {
        echo "No LFS response\n\n";
        return;
    }

    $segments = [];
    foreach (asList(data_get($rsp, 'AirSegmentList.AirSegment')) as $seg) {
        if (! is_array($seg)) continue;
        $key = attr($seg, 'Key');
        $segments[$key] = $seg;
    }

    $brands = [];
    foreach (asList(data_get($rsp, 'BrandList.Brand')) as $brand) {
        if (! is_array($brand)) continue;
        $brands[attr($brand, 'Key')] = $brand;
    }

    $fareInfos = [];
    foreach (asList(data_get($rsp, 'FareInfoList.FareInfo')) as $fi) {
        if (! is_array($fi)) continue;
        $fareInfos[attr($fi, 'Key')] = $fi;
    }

    $rows = [];
    foreach (asList(data_get($rsp, 'AirPricePointList.AirPricePoint')) as $pp) {
        if (! is_array($pp)) continue;

        $matchesEk600 = false;
        foreach (asList(data_get($pp, 'AirPricingInfo.FlightOptionsList.FlightOption')) as $fo) {
            foreach (asList(data_get($fo, 'Option')) as $opt) {
                foreach (asList(data_get($opt, 'BookingInfo')) as $bi) {
                    $segKey = attr($bi, 'SegmentRef');
                    $seg = $segments[$segKey] ?? null;
                    if ($seg && attr($seg, 'Carrier') === 'EK' && trim(attr($seg, 'FlightNumber')) === '600') {
                        $matchesEk600 = true;
                    }
                }
            }
        }
        if (! $matchesEk600) continue;

        $pricing = asList(data_get($pp, 'AirPricingInfo'))[0] ?? null;
        $fareInfo = null;
        foreach (asList(data_get($pricing, 'FareInfo')) as $fi) {
            if (is_array($fi)) { $fareInfo = $fi; break; }
        }
        if ($fareInfo === null) {
            $ref = attr(asList(data_get($pricing, 'FareInfoRef'))[0] ?? [], 'Key');
            $fareInfo = $fareInfos[$ref] ?? null;
        }

        $brandName = '';
        $brandNode = data_get($fareInfo, 'Brand');
        if (is_array($brandNode)) {
            $bkey = attr($brandNode, 'Key');
            $brandName = attr($brands[$bkey] ?? $brandNode, 'Name') ?: attr($brandNode, 'BrandID');
        }

        $rows[] = [
            'price' => attr($pp, 'TotalPrice') ?: attr($pp, 'ApproximateTotalPrice'),
            'basis' => attr($fareInfo ?? [], 'FareBasis'),
            'brand' => $brandName,
            'pp_key' => attr($pp, 'Key'),
        ];
    }

    usort($rows, fn ($a, $b) => strcmp($a['price'], $b['price']));
    foreach ($rows as $r) {
        echo sprintf("  %s | brand=%s | basis=%s\n", $r['price'], $r['brand'], $r['basis']);
    }
    echo '  count=' . count($rows) . PHP_EOL . PHP_EOL;
}

foreach ([
    'gds' => [false, false],
    'branded' => [true, false],
    'upsell' => [true, true],
] as $label => [$b, $u]) {
    $resp = $client->lowFareSearch($search, $b, $u);
    dumpRawEk600Brands($resp['parsed'] ?? null, $label);
    $fares = ek600FaresFromResponse($resp['parsed'] ?? null, $search);
    echo "Presenter EK600 fares ({$label}): " . count($fares) . PHP_EOL;
    foreach ($fares as $f) {
        echo '  ' . ($f['fare_brand'] ?? '') . ' | ' . ($f['fare_basis'] ?? '') . ' | ' . ($f['totalPrice'] ?? '') . PHP_EOL;
    }
    echo PHP_EOL;
}

// Full merged flow like loadMoreFares
$provider = new App\Services\FlightProviders\TravelportFlightProvider();
$initial = $provider->search($search);
foreach ($initial->results as $card) {
    $seg = data_get($card, 'legs.0.segments.0');
    if (! is_array($seg) || ($seg['carrier'] ?? '') !== 'EK' || trim((string) ($seg['flight_number'] ?? '')) !== '600') continue;
    $loaded = $provider->loadMoreFares($search, $card);
    echo "=== After app loadMoreFares ===" . PHP_EOL;
    foreach ($loaded['fare_options'] ?? [] as $f) {
        echo '  ' . ($f['fare_brand'] ?? '') . ' | ' . ($f['fare_basis'] ?? '') . ' | ' . ($f['totalPrice'] ?? '') . PHP_EOL;
    }
}
