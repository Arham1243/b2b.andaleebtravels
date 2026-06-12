<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Travelport\TravelportApiClient;
use App\Support\Travelport\TravelportAirPriceParser;
use App\Support\Travelport\TravelportHoldPayloadBuilder;
use App\Support\Travelport\TravelportHoldPricingInfoParser;
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
$pricingData = TravelportAirPriceParser::extract('', '');
$price = $client->airPrice(
    TravelportHoldPayloadBuilder::buildAirPriceSegments($itineraryData),
    ['ADT' => 1, 'CNN' => 0, 'INF' => 0],
    $searchData,
    $travelers,
);
$pricingData = TravelportAirPriceParser::extract((string) ($price['raw'] ?? ''), strtoupper((string) ($itineraryData['booking_code'] ?? '')));
$pricingData['passenger_types'] = TravelportHoldPayloadBuilder::passengerTypesFromTravelers($travelers);

// Hold WITHOUT host token to simulate GF fare-less hold
$holdPricing = $pricingData;
$holdPricing['host_tokens'] = [];
$hold = $client->airHold($travelers, $holdPricing);
$raw = (string) ($hold['raw'] ?? '');
echo 'hold no-token keys=' . json_encode(TravelportHoldPricingInfoParser::extractReservationKeys($hold)) . ' raw_len=' . strlen($raw) . "\n";
preg_match('/UniversalRecord[^>]*LocatorCode="([^"]+)"/i', $raw, $um);
preg_match('/AirReservation[^>]*LocatorCode="([^"]+)"/i', $raw, $am);
preg_match('/ProviderReservationInfo[^>]*LocatorCode="([^"]+)"/i', $raw, $pm);
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
echo 'held segments: ' . json_encode($heldSegments) . "\n";

$heldPrice = $client->airPrice($heldSegments, ['ADT' => 1, 'CNN' => 0, 'INF' => 0], $searchData, [], $airLoc);
echo 'held airPrice success=' . var_export($heldPrice['success'] ?? null, true) . ' error=' . ($heldPrice['error'] ?? '') . "\n";
$addXml = TravelportAirPriceParser::extractStorePriceAddXml((string) ($heldPrice['raw'] ?? ''), $keyMap);
echo 'add xml len=' . strlen($addXml) . "\n";

$store = $client->addStoredFareViaUniversalRecordModify(
    $universal, $airLoc, $version, $provider, $heldSegments,
    ['ADT' => 1, 'CNN' => 0, 'INF' => 0], $searchData, $keyMap,
);
echo 'store price: success=' . var_export($store['success'] ?? null, true) . ' error=' . ($store['error'] ?? '') . "\n";
if (! ($store['success'] ?? false)) {
    echo substr((string) ($store['raw'] ?? ''), 0, 2000) . "\n";
}
if ($store['success'] ?? false) {
    echo 'keys=' . json_encode(TravelportHoldPricingInfoParser::extractReservationKeys($store)) . "\n";
}
$client->airCancel($universal, $version);
