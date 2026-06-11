<?php

/**
 * Debug Travelport child vs adult fares for 1 Adult + 2 Children + 1 Infant.
 *
 * Usage: php scripts/debug-travelport-child-fares.php [FROM] [TO] [DATE]
 * Example: php scripts/debug-travelport-child-fares.php DXB LHR 2026-07-15
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$from = strtoupper(trim($argv[1] ?? 'DXB'));
$to = strtoupper(trim($argv[2] ?? 'LHR'));
$date = trim($argv[3] ?? '2026-07-15');

$search = [
    'trip_type' => 'one_way',
    'from' => $from,
    'to' => $to,
    'departure_date' => $date,
    'return_date' => null,
    'adults' => 1,
    'children' => 2,
    'infants' => 1,
    'child_ages' => [8, 6],
    'direct_flight' => false,
    'nearby_airports' => false,
    'student_fare' => false,
    'onward_cabin_class' => 'Economy',
];

function asList(mixed $value): array
{
    if ($value === null || $value === '') {
        return [];
    }
    if (! is_array($value)) {
        return [];
    }

    return array_is_list($value) ? $value : [$value];
}

function attr(mixed $node, string $key): ?string
{
    if (! is_array($node)) {
        return null;
    }
    if (array_key_exists($key, $node) && $node[$key] !== null && $node[$key] !== '') {
        return is_scalar($node[$key]) ? (string) $node[$key] : null;
    }
    $attrs = $node['@attributes'] ?? null;
    if (is_array($attrs) && array_key_exists($key, $attrs) && $attrs[$key] !== null && $attrs[$key] !== '') {
        return is_scalar($attrs[$key]) ? (string) $attrs[$key] : null;
    }

    return null;
}

$client = new App\Services\Travelport\TravelportApiClient();
$ref = new ReflectionClass($client);
$buildPax = $ref->getMethod('buildSearchPassengersXml');
$buildPax->setAccessible(true);
$paxXml = trim((string) $buildPax->invoke($client, $search));

echo "=== SearchPassenger XML (LFS / AirPrice) ===\n";
echo $paxXml . "\n\n";

$response = $client->lowFareSearch($search, true, true);
if (! ($response['success'] ?? false)) {
    echo 'LFS failed: ' . ($response['error'] ?? 'unknown') . "\n";
    exit(1);
}

$cards = App\Support\Travelport\TravelportSearchPresenter::toResultCards($response['parsed'], $search);
echo 'Cards returned: ' . count($cards) . "\n\n";

$rsp = data_get($response['parsed'], 'Body.LowFareSearchRsp');
$pricePoints = asList(data_get($rsp, 'AirPricePointList.AirPricePoint'));

$sampleCard = $cards[0] ?? null;
if ($sampleCard !== null) {
    echo "=== First card presenter lines ===\n";
    foreach ($sampleCard['passenger_fare_lines'] ?? [] as $line) {
        printf(
            "  %s x%d base=%.2f tax=%.2f\n",
            $line['label'] ?? '?',
            (int) ($line['count'] ?? 0),
            (float) ($line['base_per_pax'] ?? 0),
            (float) ($line['tax_per_pax'] ?? 0),
        );
    }
    if (! empty($sampleCard['passenger_fare_warning'])) {
        echo '  WARNING: ' . $sampleCard['passenger_fare_warning'] . "\n";
    }
    echo "\n";
}

$shown = 0;
foreach ($pricePoints as $pricePoint) {
    if (! is_array($pricePoint) || $shown >= 3) {
        break;
    }

    $pricingInfos = asList($pricePoint['AirPricingInfo'] ?? null);
    if ($pricingInfos === []) {
        continue;
    }

    $total = attr($pricePoint, 'TotalPrice') ?? attr($pricePoint, 'ApproximateTotalPrice') ?? '?';
    echo "=== AirPricePoint total={$total} ===\n";

    foreach ($pricingInfos as $pi) {
        if (! is_array($pi)) {
            continue;
        }

        $ptc = '?';
        $ptCount = 0;
        foreach (asList($pi['PassengerType'] ?? null) as $pt) {
            if (! is_array($pt)) {
                continue;
            }
            $ptCount++;
            if ($ptc === '?') {
                $ptc = attr($pt, 'Code') ?? '?';
            }
        }
        if ($ptc === '?') {
            foreach (asList($pi['FareInfo'] ?? null) as $fi) {
                if (! is_array($fi)) {
                    continue;
                }
                $fiPtc = attr($fi, 'PassengerTypeCode');
                if ($fiPtc !== null && $fiPtc !== '') {
                    $ptc = $fiPtc;
                    break;
                }
            }
        }

        printf(
            "  PTC=%s pax_in_info=%d BasePrice=%s Taxes=%s\n",
            $ptc,
            max(1, $ptCount),
            attr($pi, 'BasePrice') ?? '?',
            attr($pi, 'Taxes') ?? '?',
        );
    }

    $lines = App\Support\FlightPassengerFareLinesPresenter::fromTravelportPricingInfos($pricingInfos, $search);
    echo "  Presenter:\n";
    foreach ($lines as $line) {
        printf(
            "    %s x%d base=%.2f tax=%.2f\n",
            $line['label'],
            $line['count'],
            $line['base_per_pax'],
            $line['tax_per_pax'],
        );
    }

    $adultBase = null;
    $childBase = null;
    foreach ($lines as $line) {
        if (($line['type_key'] ?? '') === 'adult') {
            $adultBase = (float) ($line['base_per_pax'] ?? 0);
        }
        if (($line['type_key'] ?? '') === 'child') {
            $childBase = (float) ($line['base_per_pax'] ?? 0);
        }
    }
    if ($adultBase !== null && $childBase !== null && $childBase > 0 && $adultBase > 0) {
        echo $childBase < $adultBase
            ? "  OK: child base ({$childBase}) < adult base ({$adultBase})\n"
            : "  FAIL: child base ({$childBase}) >= adult base ({$adultBase})\n";
    }

    echo "\n";
    $shown++;
}
