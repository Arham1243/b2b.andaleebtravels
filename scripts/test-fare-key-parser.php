<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Support\Travelport\TravelportHoldPricingInfoParser;

$raw = file_get_contents(__DIR__ . '/../travelport-certification/B2BFB202606112572/01_AirCreateReservationRsp.xml');

$keys = TravelportHoldPricingInfoParser::extractReservationKeys(['raw' => $raw]);
echo 'From cert hold raw only: ' . json_encode($keys) . PHP_EOL;

$allKeys = TravelportHoldPricingInfoParser::extractKeys(['raw' => $raw]);
echo 'extractKeys alias: ' . json_encode($allKeys) . PHP_EOL;

$airPriceKey = 'xYMTo6TqWDKA4dEXEAAAAA==';
$filtered = TravelportHoldPricingInfoParser::filterQuoteKeys($keys, $airPriceKey);
echo 'After filterQuoteKeys: ' . json_encode($filtered) . PHP_EOL;

$resolved = TravelportHoldPricingInfoParser::resolveKeysForTicketing(
    ['pricing_data' => ['pricing_info_key' => $airPriceKey], 'hold_air_pricing_info_keys' => []],
    ['raw' => $raw],
);
echo 'resolveKeysForTicketing: ' . json_encode($resolved) . PHP_EOL;
