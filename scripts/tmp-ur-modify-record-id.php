<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Travelport\TravelportApiClient;
use App\Support\Travelport\TravelportAirPriceParser;
use App\Support\Travelport\TravelportHoldPayloadBuilder;
use App\Support\Travelport\TravelportHoldTravelerKeyResolver;
use App\Support\Travelport\TravelportSearchPresenter;

$client = new TravelportApiClient();
$searchData = ['trip_type' => 'one_way', 'from' => 'DXB', 'to' => 'BAH', 'departure_date' => '2026-06-25', 'adults' => 1, 'children' => 0, 'infants' => 0, 'onward_cabin_class' => 'Economy', 'direct_flight' => 0];
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
    'passengers' => [['type' => 'ADT', 'title' => 'Mr', 'first_name' => 'REC', 'last_name' => 'ID', 'dob' => '1990-05-05', 'nationality' => 'PK', 'issuing_country' => 'PK', 'passport_no' => 'AB1234567', 'passport_exp' => '2030-01-01']],
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

$endpoint = 'https://emea.universal-api.pp.travelport.com/B2BGateway/connect/uAPI/UniversalRecordService';
$creds = base64_encode('Universal API/uAPI3803196999-ff9da8ef:sR-9}8Pjr+');
$uniNs = 'http://www.travelport.com/schema/universal_v52_0';
$comNs = 'http://www.travelport.com/schema/common_v52_0';

$minimal = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <universal:UniversalRecordModifyReq xmlns:universal="{$uniNs}" xmlns:com="{$comNs}" TraceId="trace_min" AuthorizedBy="Zeeshan" TargetBranch="P7250866" Version="{$version}" ReturnRecord="true">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <universal:RecordIdentifier UniversalLocatorCode="{$universal}" ProviderCode="1G" ProviderLocatorCode="{$provider}"/>
            <universal:UniversalModifyCmd Key="mod1">
                <universal:AirAdd ReservationLocatorCode="{$airLoc}">
                    <universal:GeneralRemark UseProviderNativeMode="true"><universal:RemarkData>API TEST REMARK</universal:RemarkData></universal:GeneralRemark>
                </universal:AirAdd>
            </universal:UniversalModifyCmd>
        </universal:UniversalRecordModifyReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

$ch = curl_init($endpoint);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $minimal, CURLOPT_TIMEOUT => 90, CURLOPT_HTTPHEADER => ['Content-Type: text/xml;charset=UTF-8', 'Authorization: Basic ' . $creds]]);
$response = curl_exec($ch);
$http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "RecordIdentifier modify http={$http}\n";
echo substr((string) $response, 0, 400) . "\n";
$client->airCancel($universal, $version);
