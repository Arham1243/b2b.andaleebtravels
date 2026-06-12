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

$endpoint = 'https://emea.universal-api.pp.travelport.com/B2BGateway/connect/uAPI/UniversalRecordService';
$creds = base64_encode('Universal API/uAPI3803196999-ff9da8ef:sR-9}8Pjr+');

function postSoap(string $endpoint, string $creds, string $soap): array
{
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $soap,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTPHEADER => [
            'Content-Type: text/xml;charset=UTF-8',
            'Authorization: Basic ' . $creds,
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['http' => $httpCode, 'raw' => (string) $response];
}

$airNs = 'http://www.travelport.com/schema/air_v52_0';
$comNs = 'http://www.travelport.com/schema/common_v52_0';
$uniNs = 'http://www.travelport.com/schema/universal_v52_0';

$variants = [
    'default_ns' => function () use ($uniNs, $airNs, $comNs, $universal, $provider, $airLoc, $version, $addXml) {
        return <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <UniversalRecordModifyReq xmlns="{$uniNs}" xmlns:air="{$airNs}" xmlns:com="{$comNs}" TraceId="trace_t" AuthorizedBy="Zeeshan" TargetBranch="P7250866" Version="{$version}" ReturnRecord="true">
            <BillingPointOfSaleInfo xmlns="{$comNs}" OriginApplication="UAPI"/>
            <RecordIdentifier UniversalLocatorCode="{$universal}" ProviderCode="1G" ProviderLocatorCode="{$provider}"/>
            <UniversalModifyCmd Key="mod1">
                <AirAdd ReservationLocatorCode="{$airLoc}">{$addXml}</AirAdd>
            </UniversalModifyCmd>
        </UniversalRecordModifyReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    },
    'prefixed' => function () use ($uniNs, $airNs, $comNs, $universal, $provider, $airLoc, $version, $addXml) {
        return <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <universal:UniversalRecordModifyReq xmlns:universal="{$uniNs}" xmlns:air="{$airNs}" xmlns:com="{$comNs}" TraceId="trace_t" AuthorizedBy="Zeeshan" TargetBranch="P7250866" Version="{$version}" ReturnRecord="true">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <universal:RecordIdentifier UniversalLocatorCode="{$universal}" ProviderCode="1G" ProviderLocatorCode="{$provider}"/>
            <universal:UniversalModifyCmd Key="mod1">
                <universal:AirAdd ReservationLocatorCode="{$airLoc}">{$addXml}</universal:AirAdd>
            </universal:UniversalModifyCmd>
        </universal:UniversalRecordModifyReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    },
    'locator_code' => function () use ($uniNs, $airNs, $comNs, $universal, $airLoc, $version, $addXml) {
        return <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <universal:UniversalRecordModifyReq xmlns:universal="{$uniNs}" xmlns:air="{$airNs}" xmlns:com="{$comNs}" TraceId="trace_t" AuthorizedBy="Zeeshan" TargetBranch="P7250866" Version="{$version}" ReturnRecord="true">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <universal:UniversalRecordLocatorCode>{$universal}</universal:UniversalRecordLocatorCode>
            <universal:UniversalModifyCmd Key="mod1">
                <universal:AirAdd ReservationLocatorCode="{$airLoc}">{$addXml}</universal:AirAdd>
            </universal:UniversalModifyCmd>
        </universal:UniversalRecordModifyReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    },
    'pricing_only' => function () use ($uniNs, $airNs, $comNs, $universal, $provider, $airLoc, $version, $heldPrice) {
        // Extract just first AirPricingInfo block without inner namespace prefixes
        $raw = (string) ($heldPrice['raw'] ?? '');
        preg_match('/<air:AirPricingInfo\b([^>]*)>([\s\S]*?)<\/air:AirPricingInfo>/i', $raw, $m);
        $info = '<air:AirPricingInfo' . ($m[1] ?? '') . '>' . ($m[2] ?? '') . '</air:AirPricingInfo>';
        preg_match('/<(?:[\w-]+:)?HostToken\s+Key="([^"]+)"(?:\s+Host="([^"]*)")?>([^<]+)<\/(?:[\w-]+:)?HostToken>/i', $raw, $ht);
        $host = '';
        if ($ht) {
            $host = '<com:HostToken Key="' . htmlspecialchars($ht[1], ENT_XML1) . '">' . htmlspecialchars($ht[3], ENT_XML1) . '</com:HostToken>';
        }
        $payload = $info . $host;

        return <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <universal:UniversalRecordModifyReq xmlns:universal="{$uniNs}" xmlns:air="{$airNs}" xmlns:com="{$comNs}" TraceId="trace_t" AuthorizedBy="Zeeshan" TargetBranch="P7250866" Version="{$version}" ReturnRecord="true">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <universal:RecordIdentifier UniversalLocatorCode="{$universal}" ProviderCode="1G" ProviderLocatorCode="{$provider}"/>
            <universal:UniversalModifyCmd Key="mod1">
                <universal:AirAdd ReservationLocatorCode="{$airLoc}">{$payload}</universal:AirAdd>
            </universal:UniversalModifyCmd>
        </universal:UniversalRecordModifyReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    },
];

foreach ($variants as $name => $builder) {
    $soap = $builder();
    $result = postSoap($endpoint, $creds, $soap);
    $snippet = substr($result['raw'], 0, 300);
    echo "{$name}: http={$result['http']} snippet=" . str_replace("\n", ' ', $snippet) . "\n";
    if ($result['http'] === 200 && ! str_contains($result['raw'], 'Fault')) {
        echo "  SUCCESS\n";
        break;
    }
}

$client->airCancel($universal, $version);
