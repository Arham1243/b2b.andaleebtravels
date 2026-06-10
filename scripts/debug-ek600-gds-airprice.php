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

foreach ([
    'pub' => [false, false],
    'branded_upsell' => [true, true],
] as $label => [$b, $u]) {
    $resp = $client->lowFareSearch($search, $b, $u);
    $rsp = data_get($resp, 'parsed.Body.LowFareSearchRsp');
    if (! is_array($rsp)) continue;

    echo "=== LFS {$label} brands ===" . PHP_EOL;
    foreach (asList(data_get($rsp, 'BrandList.Brand')) as $brand) {
        if (! is_array($brand)) continue;
        $name = attr($brand, 'Name') ?: attr($brand, 'BrandID');
        if (stripos($name, 'ECO') === false && stripos($name, 'Economy') === false) continue;
        echo sprintf("  brand=%s | BDA=%s | tier=%s\n", $name, attr($brand, 'BrandedDetailsAvailable'), attr($brand, 'BrandTier'));
    }

    echo "  EK600 price points:" . PHP_EOL;
    $cards = App\Support\Travelport\TravelportSearchPresenter::toResultCards($resp['parsed'], $search);
    foreach ($cards as $card) {
        $seg = data_get($card, 'legs.0.segments.0');
        if (! is_array($seg) || ($seg['carrier'] ?? '') !== 'EK' || trim((string) ($seg['flight_number'] ?? '')) !== '600') continue;
        foreach ($card['fare_options'] ?? [] as $f) {
            echo sprintf("    %s | %s | %s | tags=%s\n", $f['fare_brand'] ?? '', $f['fare_basis'] ?? '', $f['totalPrice'] ?? '', implode(',', $f['fare_tags'] ?? []));
        }
    }
    echo PHP_EOL;
}

// Try AirPrice with inhibitBrandedContentInd if supported
$segments = [[
    'Key' => 'test', 'Group' => '0', 'ProviderCode' => '1G',
    'Carrier' => 'EK', 'FlightNumber' => '600', 'Origin' => 'DXB', 'Destination' => 'KHI',
    'DepartureTime' => '2026-06-18T07:40:00.000+04:00', 'ArrivalTime' => '2026-06-18T10:45:00.000+05:00',
    'ClassOfService' => 'L', 'Equipment' => '77W',
]];

// Get real segment key from LFS
$lfs = $client->lowFareSearch($search, false, false);
foreach (asList(data_get($lfs, 'parsed.Body.LowFareSearchRsp.AirSegmentList.AirSegment')) as $seg) {
    if (is_array($seg) && attr($seg, 'Carrier') === 'EK' && trim(attr($seg, 'FlightNumber')) === '600') {
        $segments[0]['Key'] = attr($seg, 'Key');
        $segments[0]['DepartureTime'] = attr($seg, 'DepartureTime');
        $segments[0]['ArrivalTime'] = attr($seg, 'ArrivalTime');
        break;
    }
}

$modifiers = [
    'fare_family' => '<air:BrandModifiers><air:FareFamilyDisplay ModifierType="FareFamily"/></air:BrandModifiers>',
    'basic_details' => '<air:BrandModifiers><air:BasicDetailsOnly ReturnBasicDetails="true"/></air:BrandModifiers>',
    'inhibit_brand' => ' inhibitBrandContentInd="true"',
];

$ref = new ReflectionClass($client);
$send = $ref->getMethod('sendRequest');
$send->setAccessible(true);
$trace = $ref->getMethod('generateTraceId');
$trace->setAccessible(true);
$xmlEsc = $ref->getMethod('xmlEsc');
$xmlEsc->setAccessible(true);

foreach ([
    'plain_public' => ['brand' => '', 'extra' => ''],
    'fare_family' => ['brand' => $modifiers['fare_family'], 'extra' => ''],
    'inhibit_brand_plain' => ['brand' => '', 'extra' => ' inhibitBrandContentInd="true"'],
    'inhibit_brand_ff' => ['brand' => $modifiers['fare_family'], 'extra' => ' inhibitBrandContentInd="true"'],
] as $label => $cfg) {
    $seg = $segments[0];
    $traceId = $trace->invoke($client);
    $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Body>
        <air:AirPriceReq TraceId="{$traceId}" AuthorizedBy="Zeeshan" TargetBranch="P7250866"
            xmlns:air="http://www.travelport.com/schema/air_v52_0" xmlns:com="http://www.travelport.com/schema/common_v52_0">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <air:AirItinerary>
                <air:AirSegment Key="{$seg['Key']}" Group="0" ProviderCode="1G" Carrier="EK" FlightNumber="600"
                    Origin="DXB" Destination="KHI" DepartureTime="{$seg['DepartureTime']}" ArrivalTime="{$seg['ArrivalTime']}"
                    ClassOfService="L" Status="SS" ETicketability="Yes" Equipment="77W"/>
            </air:AirItinerary>
            <air:AirPricingModifiers ETicketability="Required" FaresIndicator="PublicFaresOnly"{$cfg['extra']}>
                {$cfg['brand']}
            </air:AirPricingModifiers>
            <com:SearchPassenger Code="ADT" BookingTravelerRef="traveler_1"/>
            <air:AirPricingCommand/>
        </air:AirPriceReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

    $result = $send->invoke($client, 'AirService', $soap);
    echo "=== AirPrice {$label} ===" . PHP_EOL;
    if (! ($result['success'] ?? false)) {
        echo '  ERROR: ' . ($result['error'] ?? '') . PHP_EOL . PHP_EOL;
        continue;
    }

    $options = App\Support\Travelport\TravelportAirPricePresenter::toFareOptions($result['parsed'] ?? null, $search, []);
    foreach ($options as $o) {
        if (($o['cabin_code'] ?? '') !== 'Economy' && stripos($o['fare_brand'] ?? '', 'ECO') === false) continue;
        echo sprintf("  %s | %s | %s | tags=%s\n", $o['fare_brand'] ?? '', $o['fare_basis'] ?? '', $o['totalPrice'] ?? '', implode(',', $o['fare_tags'] ?? []));
    }
    echo PHP_EOL;
}
