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

function runVariant(array $search, bool $branded, bool $upsell): array {
    $client = new App\Services\Travelport\TravelportApiClient();
    $ref = new ReflectionClass($client);
    $buildLegs = $ref->getMethod('buildSearchAirLegsXml');
    $buildLegs->setAccessible(true);
    $buildMods = $ref->getMethod('buildSearchModifiersXml');
    $buildMods->setAccessible(true);
    $buildPax = $ref->getMethod('buildSearchPassengersXml');
    $buildPax->setAccessible(true);
    $send = $ref->getMethod('sendRequest');
    $send->setAccessible(true);
    $trace = $ref->getMethod('generateTraceId');
    $trace->setAccessible(true);

    $traceId = $trace->invoke($client);
    $brandedAttr = $branded ? 'true' : 'false';
    $upsellAttr = $upsell ? 'true' : 'false';
    $authorizedBy = 'Zeeshan';
    $targetBranch = 'P7250866';
    $airNs = 'http://www.travelport.com/schema/air_v52_0';
    $comNs = 'http://www.travelport.com/schema/common_v52_0';
    $legsXml = $buildLegs->invoke($client, $search);
    $modsXml = $buildMods->invoke($client, $search);
    $paxXml = $buildPax->invoke($client, $search);

    $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:LowFareSearchReq
            TraceId="{$traceId}"
            AuthorizedBy="{$authorizedBy}"
            TargetBranch="{$targetBranch}"
            ReturnBrandedFares="{$brandedAttr}"
            ReturnUpsellFare="{$upsellAttr}"
            xmlns:air="{$airNs}"
            xmlns:com="{$comNs}">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            {$legsXml}
            {$modsXml}
            {$paxXml}
        </air:LowFareSearchReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

    $response = $send->invoke($client, 'AirService', $soap);
    if (! ($response['success'] ?? false)) {
        return ['error' => $response['error'] ?? 'failed', 'cards' => []];
    }

    $cards = App\Support\Travelport\TravelportSearchPresenter::toResultCards($response['parsed'], $search);
    $gf505 = null;
    foreach ($cards as $card) {
        $seg = data_get($card, 'legs.0.segments.0');
        if (is_array($seg) && ($seg['carrier'] ?? '') === 'GF' && ($seg['flight_number'] ?? '') === '505') {
            $gf505 = $card;
            break;
        }
    }

    return [
        'total_cards' => count($cards),
        'total_fares' => array_sum(array_map(fn ($c) => count($c['fare_options'] ?? []), $cards)),
        'gf505_fares' => count($gf505['fare_options'] ?? []),
        'gf505_details' => array_map(fn ($f) => [
            'tags' => $f['fare_tags'] ?? [],
            'brand' => $f['fare_brand'] ?? '',
            'basis' => $f['fare_basis'] ?? '',
            'price' => $f['totalPrice'] ?? '',
        ], $gf505['fare_options'] ?? []),
    ];
}

foreach ([
    'gds_only' => [false, false],
    'branded_only' => [true, false],
    'branded_upsell' => [true, true],
] as $label => [$b, $u]) {
    echo "=== {$label} ===" . PHP_EOL;
    echo json_encode(runVariant($search, $b, $u), JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;
}
