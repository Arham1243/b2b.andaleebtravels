<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Travelport\TravelportApiClient;
use App\Support\Travelport\TravelportAirPriceParser;
use App\Support\Travelport\TravelportHoldPayloadBuilder;
use App\Support\Travelport\TravelportHoldPricingInfoParser;
use App\Support\Travelport\TravelportSearchPresenter;

function expandPricingDataForTravelers(array $pricingData, array $travelers): array
{
    $passengerTypes = TravelportHoldPayloadBuilder::passengerTypesFromTravelers($travelers);
    $pricingData['passenger_types'] = $passengerTypes;

    $fareInfosByKey = [];
    foreach ($pricingData['fare_infos'] ?? [] as $fareInfo) {
        if (! is_array($fareInfo)) {
            continue;
        }
        $key = (string) ($fareInfo['key'] ?? '');
        if ($key !== '') {
            $fareInfosByKey[$key] = $fareInfo;
        }
    }

    $bookingInfosByType = [];
    foreach ($pricingData['booking_infos'] ?? [] as $bookingInfo) {
        if (! is_array($bookingInfo)) {
            continue;
        }
        $fareRef = (string) ($bookingInfo['fare_info_ref'] ?? '');
        $fareInfo = $fareInfosByKey[$fareRef] ?? null;
        $typeCode = TravelportHoldPayloadBuilder::normalizeHoldPassengerTypeCode(
            is_array($fareInfo) ? (string) ($fareInfo['passenger_type_code'] ?? 'ADT') : 'ADT',
        );
        $bookingInfosByType[$typeCode][] = $bookingInfo;
    }

    if ($bookingInfosByType === [] && is_array($pricingData['booking_infos'] ?? null)) {
        foreach ($pricingData['booking_infos'] as $bookingInfo) {
            if (is_array($bookingInfo)) {
                $bookingInfosByType['ADT'][] = $bookingInfo;
            }
        }
    }

    $expandedBookingInfos = [];
    $typeUseIndex = [];
    foreach ($passengerTypes as $passengerType) {
        $typeCode = TravelportHoldPayloadBuilder::normalizeHoldPassengerTypeCode(
            (string) ($passengerType['code'] ?? 'ADT'),
        );
        $candidates = $bookingInfosByType[$typeCode] ?? [];
        if ($candidates === []) {
            continue;
        }
        $index = $typeUseIndex[$typeCode] ?? 0;
        $expandedBookingInfos[] = $candidates[min($index, count($candidates) - 1)];
        $typeUseIndex[$typeCode] = $index + 1;
    }

    if ($expandedBookingInfos !== []) {
        $pricingData['booking_infos'] = $expandedBookingInfos;
    }

    $neededHostRefs = array_values(array_unique(array_filter(array_map(
        static fn ($bookingInfo) => is_array($bookingInfo) ? (string) ($bookingInfo['host_token_ref'] ?? '') : '',
        $pricingData['booking_infos'] ?? [],
    ))));

    if ($neededHostRefs !== []) {
        $hostTokensByKey = [];
        foreach ($pricingData['host_tokens'] ?? [] as $hostToken) {
            if (! is_array($hostToken)) {
                continue;
            }
            $key = (string) ($hostToken['key'] ?? '');
            if ($key !== '') {
                $hostTokensByKey[$key] = $hostToken;
            }
        }

        $pricingData['host_tokens'] = array_values(array_filter(array_map(
            static fn (string $ref) => $hostTokensByKey[$ref] ?? null,
            $neededHostRefs,
        )));
    }

    return $pricingData;
}

$searchData = [
    'trip_type' => 'one_way', 'from' => 'DXB', 'to' => 'BAH', 'departure_date' => '2026-06-25',
    'adults' => 1, 'children' => 0, 'infants' => 0, 'onward_cabin_class' => 'Economy', 'direct_flight' => 0,
];
$client = new TravelportApiClient();
$search = $client->lowFareSearch($searchData);
$cards = TravelportSearchPresenter::toResultCards($search['parsed'] ?? null, $searchData);

foreach (['GF', 'EK'] as $carrier) {
    $card = null;
    foreach ($cards as $c) {
        if (($c['validating_carrier'] ?? '') === $carrier) {
            $card = $c;
            break;
        }
    }
    if ($card === null) {
        echo "{$carrier}: no card\n";
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
    $before = count($pricingData['host_tokens'] ?? []);
    $pricingData['passenger_types'] = TravelportHoldPayloadBuilder::passengerTypesFromTravelers($travelers);
    $pricingData = expandPricingDataForTravelers($pricingData, $travelers);
    $after = count($pricingData['host_tokens'] ?? []);
    $neededRefs = array_column($pricingData['booking_infos'] ?? [], 'host_token_ref');

    $hold = $client->airHold($travelers, $pricingData);
    $keys = TravelportHoldPricingInfoParser::extractReservationKeys($hold);
    echo "{$carrier}: host before={$before} after={$after} refs=" . json_encode($neededRefs) . " keys=" . json_encode($keys) . " raw_len=" . strlen((string) ($hold['raw'] ?? '')) . "\n";

    if ($hold['success'] ?? false) {
        preg_match('/UniversalRecord[^>]*LocatorCode="([^"]+)"/i', (string) ($hold['raw'] ?? ''), $um);
        preg_match('/Version="(\d+)"/i', (string) ($hold['raw'] ?? ''), $vm);
        $client->airCancel($um[1] ?? '', $vm[1] ?? '0');
    }
}
