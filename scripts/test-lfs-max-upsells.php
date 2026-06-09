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

foreach ([null, 0, 1, 2, 4] as $maxUpsells) {
    $traceId = $trace->invoke($client);
    $maxAttr = $maxUpsells === null
        ? ''
        : ' MaxNumberOfUpsellsToReturn="' . (int) $maxUpsells . '"';

    $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:LowFareSearchReq
            TraceId="{$traceId}"
            AuthorizedBy="Zeeshan"
            TargetBranch="P7250866"
            ReturnBrandedFares="true"
            ReturnUpsellFare="true"{$maxAttr}
            xmlns:air="http://www.travelport.com/schema/air_v52_0"
            xmlns:com="http://www.travelport.com/schema/common_v52_0">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            {$buildLegs->invoke($client, $search)}
            {$buildMods->invoke($client, $search)}
            {$buildPax->invoke($client, $search)}
        </air:LowFareSearchReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

    $label = $maxUpsells === null ? 'default (no Max attr)' : "MaxNumberOfUpsellsToReturn={$maxUpsells}";
    $resp = $send->invoke($client, 'AirService', $soap);

    if (! ($resp['success'] ?? false)) {
        echo "=== {$label} FAILED: " . ($resp['error'] ?? '') . PHP_EOL . PHP_EOL;
        continue;
    }

    $cards = App\Support\Travelport\TravelportSearchPresenter::toResultCards($resp['parsed'], $search);
    echo "=== {$label} ===" . PHP_EOL;

    foreach ($cards as $card) {
        $seg = data_get($card, 'legs.0.segments.0');
        if (! is_array($seg) || ($seg['carrier'] ?? '') !== 'EK' || trim((string) ($seg['flight_number'] ?? '')) !== '600') {
            continue;
        }

        echo 'EK600 fare count: ' . count($card['fare_options'] ?? []) . PHP_EOL;
        foreach ($card['fare_options'] ?? [] as $f) {
            echo '  ' . ($f['fare_brand'] ?? '') . ' | ' . ($f['fare_basis'] ?? '') . ' | ' . ($f['totalPrice'] ?? '') . PHP_EOL;
        }
    }

    echo PHP_EOL;
}
