<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Travelport\TravelportApiClient;
use App\Support\Travelport\TravelportAirPriceParser;
use App\Support\Travelport\TravelportHoldPayloadBuilder;
use App\Support\Travelport\TravelportHoldPricingInfoParser;
use App\Support\Travelport\TravelportSearchPresenter;

$carrier = strtoupper($argv[1] ?? 'GF');
$from = $argv[2] ?? 'DXB';
$to = $argv[3] ?? 'BAH';
$multi = in_array('--multi', $argv, true);

$searchData = [
    'trip_type' => 'one_way',
    'from' => $from,
    'to' => $to,
    'departure_date' => '2026-06-25',
    'adults' => $multi ? 1 : 1,
    'children' => $multi ? 2 : 0,
    'infants' => $multi ? 1 : 0,
    'onward_cabin_class' => 'Economy',
    'direct_flight' => 0,
];
if ($multi) {
    $searchData['child_ages'] = [8, 8];
}

$client = new TravelportApiClient();
$search = $client->lowFareSearch($searchData);
if (! ($search['success'] ?? false)) {
    echo 'search failed: ' . ($search['error'] ?? '?') . "\n";
    exit(1);
}

$cards = TravelportSearchPresenter::toResultCards($search['parsed'] ?? null, $searchData);
$card = null;
foreach ($cards as $c) {
    if (strtoupper((string) ($c['validating_carrier'] ?? '')) === $carrier) {
        $card = $c;
        break;
    }
}
$card = $card ?? ($cards[0] ?? null);
if ($card === null) {
    echo "no cards\n";
    exit(1);
}

echo 'carrier=' . ($card['validating_carrier'] ?? '?') . ' brand=' . ($card['fare_brand'] ?? '?') . "\n";

$fareOption = $card['fare_options'][0] ?? [];
$itineraryData = [
    'travelport_segments' => $card['travelport_segments'] ?? [],
    'booking_code' => $fareOption['booking_code'] ?? '',
];

$passengersData = [
    'lead' => ['email' => 'sales@andaleebtravels.com', 'phone' => '+971525748986', 'phone_country_code' => '971'],
    'passengers' => $multi ? [
        ['type' => 'ADT', 'title' => 'Mr', 'first_name' => 'A', 'last_name' => 'TEST', 'dob' => '1990-05-05', 'nationality' => 'PK', 'issuing_country' => 'PK', 'passport_no' => 'AB1234567', 'passport_exp' => '2030-01-01'],
        ['type' => 'CNN', 'title' => 'Mstr', 'first_name' => 'C1', 'last_name' => 'TEST', 'dob' => '2018-05-05', 'nationality' => 'PK', 'issuing_country' => 'PK', 'passport_no' => 'AB1234568', 'passport_exp' => '2030-01-01'],
        ['type' => 'CNN', 'title' => 'Mstr', 'first_name' => 'C2', 'last_name' => 'TEST', 'dob' => '2018-05-05', 'nationality' => 'PK', 'issuing_country' => 'PK', 'passport_no' => 'AB1234569', 'passport_exp' => '2030-01-01'],
        ['type' => 'INF', 'title' => 'Mstr', 'first_name' => 'I', 'last_name' => 'TEST', 'dob' => '2024-05-05', 'nationality' => 'PK', 'issuing_country' => 'PK', 'passport_no' => 'AB1234570', 'passport_exp' => '2030-01-01'],
    ] : [[
        'type' => 'ADT', 'title' => 'Mr', 'first_name' => 'DEBUG', 'last_name' => 'HOLDTEST',
        'dob' => '1990-05-05', 'nationality' => 'PK', 'issuing_country' => 'PK',
        'passport_no' => 'AB1234567', 'passport_exp' => '2030-01-01',
    ]],
];

$searchData = TravelportHoldPayloadBuilder::enrichSearchDataWithPassengerAges($searchData, $passengersData);

$travelers = TravelportHoldPayloadBuilder::buildTravelers($passengersData, $searchData);
$segments = TravelportHoldPayloadBuilder::buildAirPriceSegments($itineraryData);
$counts = [
    'ADT' => $multi ? 1 : 1,
    'CNN' => $multi ? 2 : 0,
    'INF' => $multi ? 1 : 0,
];

$price = $client->airPrice($segments, $counts, $searchData, $travelers);
if (! ($price['success'] ?? false)) {
    echo 'airPrice failed: ' . ($price['error'] ?? '?') . "\n";
    exit(1);
}

$pricingData = TravelportAirPriceParser::extract((string) ($price['raw'] ?? ''), strtoupper((string) ($itineraryData['booking_code'] ?? '')));
$pricingData['passenger_types'] = TravelportHoldPayloadBuilder::passengerTypesFromTravelers($travelers);

echo 'pricing: total=' . ($pricingData['total_price'] ?? '?')
    . ' method=' . ($pricingData['pricing_method'] ?? '?')
    . ' host_tokens=' . count($pricingData['host_tokens'] ?? [])
    . ' segments=' . count($pricingData['segments'] ?? []) . "\n";

$hold = $client->airHold($travelers, $pricingData);
$raw = (string) ($hold['raw'] ?? '');
echo 'hold success=' . var_export($hold['success'] ?? null, true)
    . ' raw_len=' . strlen($raw)
    . ' error=' . ($hold['error'] ?? '') . "\n";

$keys = TravelportHoldPricingInfoParser::extractReservationKeys($hold);
echo 'extracted keys: ' . json_encode($keys) . "\n";

$checks = [
    'StoredFare' => str_contains($raw, 'PricingType="StoredFare"'),
    'AirPricingInfo' => (bool) preg_match('/<(?:[\w-]+:)?AirPricingInfo\b/i', $raw),
    'AirSolutionChangedInfo' => (bool) preg_match('/<(?:[\w-]+:)?AirSolutionChangedInfo\b/i', $raw),
    'HostToken' => (bool) preg_match('/<(?:[\w-]+:)?HostToken\b/i', $raw),
];

echo 'xml checks: ' . json_encode($checks) . "\n";

if (preg_match_all('/<(?:[\w-]+:)?AirPricingInfo\b[^>]*>/i', $raw, $apiMatches)) {
    echo 'AirPricingInfo tags (' . count($apiMatches[0]) . "):\n";
    foreach ($apiMatches[0] as $tag) {
        echo '  ' . substr($tag, 0, 200) . "\n";
    }
}

preg_match('/<universal:UniversalRecord[^>]*LocatorCode="([^"]+)"/i', $raw, $um);
$universalLocator = $um[1] ?? '';
if ($universalLocator !== '') {
    sleep(2);
    $retrieve = $client->universalRecordRetrieve($universalLocator);
    $rRaw = (string) ($retrieve['raw'] ?? '');
    $rKeys = TravelportHoldPricingInfoParser::extractReservationKeys($retrieve);
    echo "retrieve $universalLocator raw_len=" . strlen($rRaw) . ' keys=' . json_encode($rKeys) . "\n";
    echo 'retrieve StoredFare=' . var_export(str_contains($rRaw, 'PricingType="StoredFare"'), true) . "\n";

    // cleanup
    preg_match('/Version="(\d+)"/i', $rRaw, $vm);
    $client->airCancel($universalLocator, $vm[1] ?? '0');
    echo "cancelled $universalLocator\n";
}
