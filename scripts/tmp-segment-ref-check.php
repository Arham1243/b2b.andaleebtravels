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
    'passengers' => [['type' => 'ADT', 'title' => 'Mr', 'first_name' => 'SEG', 'last_name' => 'REF', 'dob' => '1990-05-05', 'nationality' => 'PK', 'issuing_country' => 'PK', 'passport_no' => 'AB1234567', 'passport_exp' => '2030-01-01']],
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
$universal = $um[1] ?? '';
sleep(2);
$retrieve = $client->universalRecordRetrieve($universal);
$heldSegs = TravelportHoldTravelerKeyResolver::extractHeldAirSegments($retrieve);
echo 'held PNR segment keys: ' . json_encode(array_column($heldSegs, 'key')) . "\n";

preg_match('/AirReservation[^>]*LocatorCode="([^"]+)"/i', (string) ($hold['raw'] ?? ''), $am);
$airLoc = $am[1] ?? '';
$heldSegments = TravelportAirPriceParser::heldSegmentsToAirPriceFormat($heldSegs);
$heldPrice = $client->airPrice($heldSegments, ['ADT' => 1, 'CNN' => 0, 'INF' => 0], $searchData, [], $airLoc);
preg_match_all('/SegmentRef="([^"]+)"/i', (string) ($heldPrice['raw'] ?? ''), $refs);
echo 'held airPrice SegmentRefs: ' . json_encode(array_values(array_unique($refs[1] ?? []))) . "\n";
preg_match_all('/AirSegmentRef="([^"]+)"/i', (string) ($heldPrice['raw'] ?? ''), $arefs);
echo 'held airPrice AirSegmentRefs: ' . json_encode(array_values(array_unique($arefs[1] ?? []))) . "\n";

preg_match('/Version="(\d+)"/i', (string) ($retrieve['raw'] ?? ''), $vm);
$client->airCancel($universal, $vm[1] ?? '0');
