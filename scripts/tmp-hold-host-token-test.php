<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Travelport\TravelportApiClient;
use App\Support\Travelport\TravelportAirPriceParser;
use App\Support\Travelport\TravelportHoldPayloadBuilder;
use App\Support\Travelport\TravelportHoldPricingInfoParser;
use App\Support\Travelport\TravelportSearchPresenter;

$searchData = [
    'trip_type' => 'one_way', 'from' => 'DXB', 'to' => 'BAH', 'departure_date' => '2026-06-25',
    'adults' => 1, 'children' => 0, 'infants' => 0, 'onward_cabin_class' => 'Economy', 'direct_flight' => 0,
];
$client = new TravelportApiClient();
$search = $client->lowFareSearch($searchData);
$cards = TravelportSearchPresenter::toResultCards($search['parsed'] ?? null, $searchData);

foreach (['GF', 'FZ', 'EK'] as $carrier) {
    $card = null;
    foreach ($cards as $c) {
        if (($c['validating_carrier'] ?? '') === $carrier) {
            $card = $c;
            break;
        }
    }
    if ($card === null) {
        echo "{$carrier}: no card found\n";
        continue;
    }

    $fareOption = $card['fare_options'][0] ?? [];
    $itineraryData = ['travelport_segments' => $card['travelport_segments'] ?? [], 'booking_code' => $fareOption['booking_code'] ?? ''];
    $passengersData = [
        'lead' => ['email' => 'sales@andaleebtravels.com', 'phone' => '+971525748986', 'phone_country_code' => '971'],
        'passengers' => [['type' => 'ADT', 'title' => 'Mr', 'first_name' => 'HOLD', 'last_name' => 'TEST', 'dob' => '1990-05-05', 'nationality' => 'PK', 'issuing_country' => 'PK', 'passport_no' => 'AB1234567', 'passport_exp' => '2030-01-01']],
    ];
    $travelers = TravelportHoldPayloadBuilder::buildTravelers($passengersData, $searchData);
    $segments = TravelportHoldPayloadBuilder::buildAirPriceSegments($itineraryData);
    $price = $client->airPrice($segments, ['ADT' => 1, 'CNN' => 0, 'INF' => 0], $searchData, $travelers);
    $pricingData = TravelportAirPriceParser::extract((string) ($price['raw'] ?? ''), strtoupper((string) ($itineraryData['booking_code'] ?? '')));
    $pricingData['passenger_types'] = TravelportHoldPayloadBuilder::passengerTypesFromTravelers($travelers);
    $hostCount = count($pricingData['host_tokens'] ?? []);
    $bookingHostRefs = array_filter(array_column($pricingData['booking_infos'] ?? [], 'host_token_ref'));

    $hold = $client->airHold($travelers, $pricingData);
    $keys = TravelportHoldPricingInfoParser::extractReservationKeys($hold);
    $rawLen = strlen((string) ($hold['raw'] ?? ''));
    echo "{$carrier}: host_tokens={$hostCount} booking_host_refs=" . count($bookingHostRefs) . " hold_ok=" . var_export($hold['success'] ?? null, true) . " keys=" . json_encode($keys) . " raw_len={$rawLen}\n";

    if ($hold['success'] ?? false) {
        preg_match('/UniversalRecord[^>]*LocatorCode="([^"]+)"/i', (string) ($hold['raw'] ?? ''), $um);
        preg_match('/Version="(\d+)"/i', (string) ($hold['raw'] ?? ''), $vm);
        $client->airCancel($um[1] ?? '', $vm[1] ?? '0');
    }
}
