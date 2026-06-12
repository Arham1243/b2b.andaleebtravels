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

$client = new TravelportApiClient();
$searchData = [
    'trip_type' => 'one_way', 'from' => 'DXB', 'to' => 'LHR', 'departure_date' => '2026-06-25',
    'adults' => 1, 'children' => 0, 'infants' => 0, 'onward_cabin_class' => 'Economy', 'direct_flight' => 0,
];
$search = $client->lowFareSearch($searchData);
$cards = TravelportSearchPresenter::toResultCards($search['parsed'] ?? null, $searchData);

$card = null;
foreach ($cards as $c) {
    if (($c['validating_carrier'] ?? '') === 'QR' && count($c['travelport_segments'] ?? []) >= 2) {
        $card = $c;
        break;
    }
}
if ($card === null) {
    echo "No QR connecting card found\n";
    exit(0);
}

$fareOption = $card['fare_options'][0] ?? [];
$itineraryData = ['travelport_segments' => $card['travelport_segments'] ?? [], 'booking_code' => $fareOption['booking_code'] ?? ''];
$passengersData = [
    'lead' => ['email' => 'sales@andaleebtravels.com', 'phone' => '+971525748986', 'phone_country_code' => '971'],
    'passengers' => [['type' => 'ADT', 'title' => 'Mr', 'first_name' => 'QR', 'last_name' => 'TEST', 'dob' => '1990-05-05', 'nationality' => 'PK', 'issuing_country' => 'PK', 'passport_no' => 'AB1234567', 'passport_exp' => '2030-01-01']],
];
$travelers = TravelportHoldPayloadBuilder::buildTravelers($passengersData, $searchData);
$segments = TravelportHoldPayloadBuilder::buildAirPriceSegments($itineraryData);
$price = $client->airPrice($segments, ['ADT' => 1, 'CNN' => 0, 'INF' => 0], $searchData, $travelers);
$pricingData = TravelportAirPriceParser::extract((string) ($price['raw'] ?? ''), strtoupper((string) ($itineraryData['booking_code'] ?? '')));
$pricingData['passenger_types'] = TravelportHoldPayloadBuilder::passengerTypesFromTravelers($travelers);

echo 'segments=' . count($pricingData['segments'] ?? []) . ' booking_infos=' . count($pricingData['booking_infos'] ?? []) . ' host_tokens=' . count($pricingData['host_tokens'] ?? []) . "\n";

$hold = $client->airHold($travelers, $pricingData);
$keys = TravelportHoldPricingInfoParser::extractReservationKeys($hold);
echo 'hold ok=' . var_export($hold['success'] ?? null, true) . ' keys=' . json_encode($keys) . ' raw_len=' . strlen((string) ($hold['raw'] ?? '')) . "\n";

if (! ($hold['success'] ?? false)) {
    exit(1);
}

preg_match('/UniversalRecord[^>]*LocatorCode="([^"]+)"/i', (string) ($hold['raw'] ?? ''), $um);
preg_match('/AirReservation[^>]*LocatorCode="([^"]+)"/i', (string) ($hold['raw'] ?? ''), $am);
preg_match('/ProviderReservationInfo[^>]*LocatorCode="([^"]+)"/i', (string) ($hold['raw'] ?? ''), $pm);
$universal = $um[1] ?? '';
$airLoc = $am[1] ?? '';
$provider = $pm[1] ?? '';

if ($keys !== []) {
    $client->airCancel($universal, '0');
    exit(0);
}

sleep(2);
$retrieve = $client->universalRecordRetrieve($universal);
preg_match('/Version="(\d+)"/i', (string) ($retrieve['raw'] ?? ''), $vm);
$keyMap = TravelportHoldTravelerKeyResolver::resolveRequestToGdsKeyMapFromSources($travelers, $retrieve, $hold);
$gdsRefs = array_values(array_filter(array_map(static fn ($t) => is_array($t) ? trim((string) ($t['key'] ?? '')) : '', $travelers)));
foreach ($keyMap as $from => $to) {
    foreach ($travelers as $i => $traveler) {
        if (is_array($traveler) && (string) ($traveler['key'] ?? '') === $from) {
            $travelers[$i]['key'] = $to;
        }
    }
}
$gdsRefs = array_values(array_filter(array_map(static fn ($t) => is_array($t) ? trim((string) ($t['key'] ?? '')) : '', $travelers)));

$heldSegments = TravelportAirPriceParser::heldSegmentsToAirPriceFormat(
    TravelportHoldTravelerKeyResolver::extractHeldAirSegments($retrieve),
);

$store = $client->addStoredFareViaUniversalRecordModify(
    $universal, $airLoc, $vm[1] ?? '0', $provider, $heldSegments,
    ['ADT' => 1, 'CNN' => 0, 'INF' => 0], $searchData, $keyMap, $gdsRefs,
);
echo 'store price ok=' . var_export($store['success'] ?? null, true) . ' error=' . ($store['error'] ?? '') . "\n";
if ($store['success'] ?? false) {
    echo 'keys=' . json_encode(TravelportHoldPricingInfoParser::extractReservationKeys($store)) . "\n";
}
$client->airCancel($universal, $vm[1] ?? '0');
