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
$resp = $client->lowFareSearch($search, true, true);
$rsp = data_get($resp, 'parsed.Body.LowFareSearchRsp');

function attr($node, string $name): string {
    if (! is_array($node)) return '';
    return (string) ($node['@attributes'][$name] ?? $node[$name] ?? '');
}
function asList($v): array {
    if (! is_array($v)) return [];
    return array_is_list($v) ? $v : [$v];
}

$segmentsByKey = [];
foreach (asList(data_get($rsp, 'AirSegmentList.AirSegment')) as $seg) {
    if (is_array($seg)) {
        $segmentsByKey[attr($seg, 'Key')] = $seg;
    }
}

$ekSegment = null;
foreach ($segmentsByKey as $seg) {
    if (attr($seg, 'Carrier') === 'EK' && trim(attr($seg, 'FlightNumber')) === '600') {
        $ekSegment = [
            'Key' => attr($seg, 'Key'),
            'Group' => attr($seg, 'Group', '0'),
            'ProviderCode' => attr($seg, 'ProviderCode') ?: '1G',
            'Carrier' => attr($seg, 'Carrier'),
            'FlightNumber' => attr($seg, 'FlightNumber'),
            'Origin' => attr($seg, 'Origin'),
            'Destination' => attr($seg, 'Destination'),
            'DepartureTime' => attr($seg, 'DepartureTime'),
            'ArrivalTime' => attr($seg, 'ArrivalTime'),
            'ClassOfService' => attr($seg, 'ClassOfService') ?: 'K',
            'Equipment' => attr($seg, 'Equipment', '777'),
        ];
        break;
    }
}

if (! $ekSegment) {
    echo "EK600 segment not found\n";
    exit(1);
}

echo "EK600 segment attrs from LFS:\n";
print_r($ekSegment);
echo PHP_EOL;

$modifiers = [
    'none' => '',
    'fare_family' => <<<'XML'

            <air:BrandModifiers>
                <air:FareFamilyDisplay ModifierType="FareFamily"/>
            </air:BrandModifiers>
XML,
    'lowest_in_brand' => <<<'XML'

            <air:BrandModifiers>
                <air:FareFamilyDisplay ModifierType="LowestFareInBrand"/>
            </air:BrandModifiers>
XML,
];

foreach ($modifiers as $label => $brandXml) {
    $traceId = 'test-' . bin2hex(random_bytes(4));
    $authorizedBy = 'Zeeshan';
    $targetBranch = 'P7250866';
    $airNs = 'http://www.travelport.com/schema/air_v52_0';
    $comNs = 'http://www.travelport.com/schema/common_v52_0';
    $seg = $ekSegment;
    $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:AirPriceReq TraceId="{$traceId}" AuthorizedBy="{$authorizedBy}" TargetBranch="{$targetBranch}"
            xmlns:air="{$airNs}" xmlns:com="{$comNs}">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <air:AirItinerary>
                <air:AirSegment Key="{$seg['Key']}" Group="{$seg['Group']}" ProviderCode="{$seg['ProviderCode']}"
                    Carrier="{$seg['Carrier']}" FlightNumber="{$seg['FlightNumber']}"
                    Origin="{$seg['Origin']}" Destination="{$seg['Destination']}"
                    DepartureTime="{$seg['DepartureTime']}" ArrivalTime="{$seg['ArrivalTime']}"
                    ClassOfService="{$seg['ClassOfService']}" Status="SS" ETicketability="Yes"
                    Equipment="{$seg['Equipment']}"/>
            </air:AirItinerary>
            <air:AirPricingModifiers ETicketability="Required" FaresIndicator="PublicFaresOnly">
                {$brandXml}
            </air:AirPricingModifiers>
            <com:SearchPassenger Code="ADT" BookingTravelerRef="traveler_1"/>
            <air:AirPricingCommand/>
        </air:AirPriceReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

    $ref = new ReflectionClass($client);
    $send = $ref->getMethod('sendRequest');
    $send->setAccessible(true);
    $result = $send->invoke($client, 'AirService', $soap);

    echo "=== AirPrice {$label} ===" . PHP_EOL;
    if (! ($result['success'] ?? false)) {
        echo '  ERROR: ' . ($result['error'] ?? 'failed') . PHP_EOL . PHP_EOL;
        continue;
    }

    $raw = $result['raw'] ?? '';
    echo '  FLEX PLUS in response: ' . (stripos($raw, 'FLEX PLUS') !== false ? 'YES' : 'NO') . PHP_EOL;

    $prices = [];
    if (preg_match_all('/TotalPrice="([^"]+)"/', $raw, $m)) {
        $prices = array_unique($m[1]);
    }
    sort($prices);
    echo '  TotalPrice values: ' . implode(', ', $prices) . PHP_EOL;

    if (preg_match_all('/Name="([^"]+)"/', $raw, $names)) {
        $brandNames = array_values(array_unique(array_filter($names[1], fn ($n) => stripos($n, 'ECO') !== false || stripos($n, 'FLEX') !== false || stripos($n, 'SAVER') !== false)));
        echo '  Brand-ish names: ' . implode(', ', $brandNames) . PHP_EOL;
    }
    echo PHP_EOL;
}
