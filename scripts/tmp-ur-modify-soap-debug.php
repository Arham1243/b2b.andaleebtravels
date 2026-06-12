<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Travelport\TravelportApiClient;
use App\Support\Travelport\TravelportAirPriceParser;
use App\Support\Travelport\TravelportHoldPayloadBuilder;
use App\Support\Travelport\TravelportHoldTravelerKeyResolver;
use App\Support\Travelport\TravelportSearchPresenter;

$searchData = [
    'trip_type' => 'one_way', 'from' => 'DXB', 'to' => 'BAH', 'departure_date' => '2026-06-25',
    'adults' => 1, 'children' => 0, 'infants' => 0, 'onward_cabin_class' => 'Economy', 'direct_flight' => 0,
];
$client = new TravelportApiClient();
$search = $client->lowFareSearch($searchData);
$cards = TravelportSearchPresenter::toResultCards($search['parsed'] ?? null, $searchData);
$card = null;
foreach ($cards as $c) {
    if (($c['validating_carrier'] ?? '') === 'GF') { $card = $c; break; }
}
$fareOption = $card['fare_options'][0] ?? [];
$itineraryData = ['travelport_segments' => $card['travelport_segments'] ?? [], 'booking_code' => $fareOption['booking_code'] ?? ''];
$passengersData = [
    'lead' => ['email' => 'sales@andaleebtravels.com', 'phone' => '+971525748986', 'phone_country_code' => '971'],
    'passengers' => [['type' => 'ADT', 'title' => 'Mr', 'first_name' => 'STORE', 'last_name' => 'PRICE', 'dob' => '1990-05-05', 'nationality' => 'PK', 'issuing_country' => 'PK', 'passport_no' => 'AB1234567', 'passport_exp' => '2030-01-01']],
];
$travelers = TravelportHoldPayloadBuilder::buildTravelers($passengersData, $searchData);
$segments = TravelportHoldPayloadBuilder::buildAirPriceSegments($itineraryData);
$price = $client->airPrice($segments, ['ADT' => 1, 'CNN' => 0, 'INF' => 0], $searchData, $travelers);
$pricingData = TravelportAirPriceParser::extract((string) ($price['raw'] ?? ''), strtoupper((string) ($itineraryData['booking_code'] ?? '')));
$pricingData['passenger_types'] = TravelportHoldPayloadBuilder::passengerTypesFromTravelers($travelers);

$holdPricing = $pricingData;
$holdPricing['host_tokens'] = [];
$hold = $client->airHold($travelers, $holdPricing);
preg_match('/UniversalRecord[^>]*LocatorCode="([^"]+)"/i', (string) ($hold['raw'] ?? ''), $um);
preg_match('/AirReservation[^>]*LocatorCode="([^"]+)"/i', (string) ($hold['raw'] ?? ''), $am);
preg_match('/ProviderReservationInfo[^>]*LocatorCode="([^"]+)"/i', (string) ($hold['raw'] ?? ''), $pm);
$universal = $um[1] ?? '';
$airLoc = $am[1] ?? '';
$provider = $pm[1] ?? '';

sleep(2);
$retrieve = $client->universalRecordRetrieve($universal);
preg_match('/Version="(\d+)"/i', (string) ($retrieve['raw'] ?? ''), $vm);
$version = $vm[1] ?? '0';
$keyMap = TravelportHoldTravelerKeyResolver::resolveRequestToGdsKeyMapFromSources($travelers, $retrieve, $hold);
$heldSegments = TravelportAirPriceParser::heldSegmentsToAirPriceFormat(
    TravelportHoldTravelerKeyResolver::extractHeldAirSegments($retrieve),
);

$heldPrice = $client->airPrice($heldSegments, ['ADT' => 1, 'CNN' => 0, 'INF' => 0], $searchData, [], $airLoc);
$addXml = TravelportAirPriceParser::extractStorePriceAddXml((string) ($heldPrice['raw'] ?? ''), $keyMap);

// Build SOAP like addStoredFareViaUniversalRecordModify
$traceId = 'trace_debug';
$authorizedBy = 'Zeeshan';
$targetBranch = 'P7250866';
$airNs = 'http://www.travelport.com/schema/air_v52_0';
$comNs = 'http://www.travelport.com/schema/common_v52_0';
$uniNs = 'http://www.travelport.com/schema/universal_v52_0';
$providerCode = '1G';

$soapA = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <UniversalRecordModifyReq
            xmlns="{$uniNs}"
            xmlns:air="{$airNs}"
            xmlns:com="{$comNs}"
            TraceId="{$traceId}"
            AuthorizedBy="{$authorizedBy}"
            TargetBranch="{$targetBranch}"
            Version="{$version}"
            ReturnRecord="true">
            <BillingPointOfSaleInfo xmlns="{$comNs}" OriginApplication="UAPI"/>
            <RecordIdentifier UniversalLocatorCode="{$universal}" ProviderCode="{$providerCode}" ProviderLocatorCode="{$provider}"/>
            <UniversalModifyCmd Key="mod1">
                <AirAdd ReservationLocatorCode="{$airLoc}">
{$addXml}
                </AirAdd>
            </UniversalModifyCmd>
        </UniversalRecordModifyReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

$soapB = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <universal:UniversalRecordModifyReq
            xmlns:universal="{$uniNs}"
            xmlns:air="{$airNs}"
            xmlns:com="{$comNs}"
            TraceId="{$traceId}"
            AuthorizedBy="{$authorizedBy}"
            TargetBranch="{$targetBranch}"
            Version="{$version}"
            ReturnRecord="true">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <universal:RecordIdentifier UniversalLocatorCode="{$universal}" ProviderCode="{$providerCode}" ProviderLocatorCode="{$provider}"/>
            <universal:UniversalModifyCmd Key="mod1">
                <universal:AirAdd ReservationLocatorCode="{$airLoc}">
{$addXml}
                </universal:AirAdd>
            </universal:UniversalModifyCmd>
        </universal:UniversalRecordModifyReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

foreach (['A' => $soapA, 'B' => $soapB] as $label => $soap) {
    libxml_use_internal_errors(true);
    $doc = simplexml_load_string($soap);
    $valid = $doc !== false;
    $errors = libxml_get_errors();
    echo "SOAP {$label}: valid=" . var_export($valid, true) . ' len=' . strlen($soap) . ' errors=' . count($errors) . "\n";
    if (! $valid && $errors !== []) {
        echo '  first: ' . trim($errors[0]->message) . "\n";
    }
    file_put_contents(__DIR__ . "/tmp-ur-modify-soap-{$label}.xml", $soap);
}

// Check for illegal chars in addXml
if (preg_match('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', $addXml)) {
    echo "addXml has illegal XML chars\n";
}
if (str_contains($addXml, '&') && ! preg_match('/&(amp|lt|gt|quot|apos);/', $addXml)) {
    echo "addXml may have unescaped ampersands\n";
}

$client->airCancel($universal, $version);
