<?php

namespace App\Support\Travelport;

use App\Support\FlightCabinPreference;
use App\Support\FlightListingMetaBuilder;
use App\Support\FlightPassengerFareLinesPresenter;
use Carbon\Carbon;

class TravelportSearchPresenter
{
    /**
     * @param  array<string, mixed>|null  $parsed
     * @param  array<string, mixed>  $searchData
     * @return list<array<string, mixed>>
     */
    public static function toResultCards(?array $parsed, array $searchData = []): array
    {
        if (! is_array($parsed)) {
            return [];
        }

        $rsp = data_get($parsed, 'Body.LowFareSearchRsp');
        if (! is_array($rsp)) {
            return [];
        }

        $segmentsByKey = self::indexByKey(data_get($rsp, 'AirSegmentList.AirSegment'));
        $fareInfosByKey = self::indexByKey(data_get($rsp, 'FareInfoList.FareInfo'));
        $brandsByKey = self::indexByKey(data_get($rsp, 'BrandList.Brand'));
        $pricePoints = self::asList(data_get($rsp, 'AirPricePointList.AirPricePoint'));

        $results = [];

        foreach ($pricePoints as $pricePoint) {
            if (! is_array($pricePoint)) {
                continue;
            }

            $card = self::buildCard($pricePoint, $segmentsByKey, $fareInfosByKey, $brandsByKey, $searchData);
            if ($card !== null) {
                $results[] = $card;
            }
        }

        $results = self::groupCardsByRouting($results);

        usort($results, static fn (array $a, array $b): int => ((float) ($a['totalPrice'] ?? 0)) <=> ((float) ($b['totalPrice'] ?? 0)));

        return $results;
    }

    /**
     * @param  list<list<array<string, mixed>>>  $cardLists
     * @return list<array<string, mixed>>
     */
    public static function mergeResultCardLists(array ...$cardLists): array
    {
        $allCards = [];

        foreach ($cardLists as $cards) {
            foreach ($cards as $card) {
                if (is_array($card)) {
                    $allCards[] = $card;
                }
            }
        }

        return self::groupCardsByRouting($allCards);
    }

    /**
     * @param  list<array<string, mixed>>  $cards
     */
    public static function findCardByRoutingSignature(array $cards, string $signature): ?array
    {
        foreach ($cards as $card) {
            if (! is_array($card)) {
                continue;
            }

            if (self::routingSignature($card) === $signature) {
                return $card;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $card
     * @param  list<array<string, mixed>>  $incomingFareOptions
     * @return array<string, mixed>
     */
    public static function enrichCardFareOptions(array $card, array $incomingFareOptions): array
    {
        $mergedOptions = self::mergeFareOptions(
            $card['fare_options'] ?? [],
            $incomingFareOptions,
        );

        $card['fare_options'] = $mergedOptions;
        $card['totalPrice'] = (float) ($mergedOptions[0]['totalPrice'] ?? $card['totalPrice'] ?? 0);
        $card['supplierPrice'] = $card['totalPrice'];

        $cheapest = $mergedOptions[0] ?? [];
        $card['fare_brand'] = $cheapest['fare_brand'] ?? $card['fare_brand'] ?? null;
        $card['baggage_details'] = $cheapest['baggage_details'] ?? $card['baggage_details'] ?? [];
        $card['baggage_notes'] = $cheapest['baggage_notes'] ?? $card['baggage_notes'] ?? '';
        $card['fare_rules'] = $cheapest['fare_rules'] ?? $card['fare_rules'] ?? [];
        $card['non_refundable'] = (bool) ($cheapest['non_refundable'] ?? $card['non_refundable'] ?? false);
        $card['fare_tags'] = $cheapest['fare_tags'] ?? $card['fare_tags'] ?? ['published'];
        $card['passenger_fare_lines'] = $cheapest['passenger_fare_lines'] ?? $card['passenger_fare_lines'] ?? [];
        $card['supplierBasePrice'] = $cheapest['supplierBasePrice'] ?? $card['supplierBasePrice'] ?? null;
        $card['supplierTaxes'] = $cheapest['supplierTaxes'] ?? $card['supplierTaxes'] ?? null;
        $card['basePrice'] = $cheapest['basePrice'] ?? $card['basePrice'] ?? null;
        $card['taxes'] = $cheapest['taxes'] ?? $card['taxes'] ?? null;
        $card['listing_meta'] = FlightListingMetaBuilder::fromLegs(
            $card['legs'] ?? [],
            $card['totalPrice'],
            ['tags' => $card['fare_tags']],
        );

        return $card;
    }

    /**
     * Drop generic GDS "Economy" when a branded GDS fare exists for the same fare basis.
     *
     * @param  list<array<string, mixed>>  $fareOptions
     * @return list<array<string, mixed>>
     */
    public static function collapseRedundantGdsEconomyFares(array $fareOptions): array
    {
        $brandedGdsBases = [];
        foreach ($fareOptions as $option) {
            if (! is_array($option)) {
                continue;
            }

            $tags = is_array($option['fare_tags'] ?? null) ? $option['fare_tags'] : [];
            if (! in_array('gds', array_map('strtolower', $tags), true)) {
                continue;
            }

            $brand = strtoupper(trim((string) ($option['fare_brand'] ?? '')));
            if ($brand === '' || $brand === 'ECONOMY') {
                continue;
            }

            $basis = strtoupper(trim((string) ($option['fare_basis'] ?? '')));
            if ($basis !== '') {
                $brandedGdsBases[$basis] = true;
            }
        }

        if ($brandedGdsBases === []) {
            return $fareOptions;
        }

        return array_values(array_filter($fareOptions, static function (array $option) use ($brandedGdsBases): bool {
            $tags = is_array($option['fare_tags'] ?? null) ? $option['fare_tags'] : [];
            if (! in_array('gds', array_map('strtolower', $tags), true)) {
                return true;
            }

            $brand = strtoupper(trim((string) ($option['fare_brand'] ?? '')));
            if ($brand !== 'ECONOMY') {
                return true;
            }

            $basis = strtoupper(trim((string) ($option['fare_basis'] ?? '')));

            return $basis === '' || ! isset($brandedGdsBases[$basis]);
        }));
    }

    /**
     * Rebuild AirFareRules request from a persisted LowFareSearch response.
     *
     * @param  array<string, mixed>  $searchResponse
     * @param  list<array<string, mixed>>  $legs
     * @return array<string, mixed>|null
     */
    public static function fareRuleRequestForPricePoint(array $searchResponse, string $pricePointKey, array $legs): ?array
    {
        $pricePointKey = trim($pricePointKey);
        if ($pricePointKey === '' || $legs === []) {
            return null;
        }

        $rsp = data_get($searchResponse, 'Body.LowFareSearchRsp');
        if (! is_array($rsp)) {
            return null;
        }

        $fareInfosByKey = self::indexByKey(data_get($rsp, 'FareInfoList.FareInfo'));

        foreach (self::asList(data_get($rsp, 'AirPricePointList.AirPricePoint')) as $pricePoint) {
            if (! is_array($pricePoint)) {
                continue;
            }

            if ((string) self::attr($pricePoint, 'Key', '') !== $pricePointKey) {
                continue;
            }

            $primaryPricingInfo = null;
            $primaryFareInfo = null;

            foreach (self::asList(data_get($pricePoint, 'AirPricingInfo')) as $pricingInfo) {
                if (! is_array($pricingInfo)) {
                    continue;
                }

                $primaryPricingInfo = $primaryPricingInfo ?: $pricingInfo;
                $primaryFareInfo = $primaryFareInfo ?: self::resolveFareInfoNode($pricingInfo, $fareInfosByKey);
            }

            if ($primaryPricingInfo === null || $primaryFareInfo === null) {
                return null;
            }

            return TravelportFareRulesPresenter::fareRuleRequest($primaryFareInfo, $primaryPricingInfo, $legs);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $pricePoint
     * @param  array<string, array<string, mixed>>  $segmentsByKey
     * @param  array<string, array<string, mixed>>  $fareInfosByKey
     * @param  array<string, array<string, mixed>>  $brandsByKey
     * @param  array<string, mixed>  $searchData
     */
    private static function buildCard(
        array $pricePoint,
        array $segmentsByKey,
        array $fareInfosByKey,
        array $brandsByKey,
        array $searchData,
    ): ?array {
        $key = (string) self::attr($pricePoint, 'Key', '');
        $totalPrice = self::extractTotalPrice($pricePoint);
        if ($totalPrice === null || $totalPrice <= 0) {
            return null;
        }

        $money = self::parseMoneyValue(
            self::attr($pricePoint, 'TotalPrice')
                ?? self::attr($pricePoint, 'ApproximateTotalPrice')
                ?? self::attr(data_get($pricePoint, 'AirPricingInfo'), 'TotalPrice')
        );
        $currency = $money['currency'] ?? 'AED';

        $pricingInfos = self::asList(data_get($pricePoint, 'AirPricingInfo'));
        $legs = [];
        $seenLegSignatures = [];
        $rawSegments = [];
        $rawSegmentKeys = [];
        $validatingCarrier = null;
        $fareBasis = null;
        $fareBrand = null;
        $bookingCode = null;
        $cabinClass = null;
        $seatsAvailable = null;
        $nonRefundable = false;
        $baggageDetails = TravelportBaggagePresenter::fromFareInfo(null, null, [], null);
        $basePrice = null;
        $taxes = null;
        $passengerFareLines = FlightPassengerFareLinesPresenter::fromTravelportPricingInfos($pricingInfos, $searchData);
        $passengerFareTotals = FlightPassengerFareLinesPresenter::aggregateTotals($passengerFareLines);
        if (($passengerFareTotals['base'] ?? 0) > 0) {
            $basePrice = $passengerFareTotals['base'];
        }
        if (($passengerFareTotals['tax'] ?? 0) > 0) {
            $taxes = $passengerFareTotals['tax'];
        }
        $primaryFareInfo = null;
        $primaryPricingInfo = null;
        $legsCollected = false;

        foreach ($pricingInfos as $pricingInfo) {
            if (! is_array($pricingInfo)) {
                continue;
            }

            $validatingCarrier = $validatingCarrier ?: self::attr($pricingInfo, 'PlatingCarrier');
            $nonRefundable = strtolower((string) self::attr($pricingInfo, 'Refundable', 'true')) === 'false';

            $primaryPricingInfo = $primaryPricingInfo ?: $pricingInfo;
            $primaryFareInfo = $primaryFareInfo ?: self::resolveFareInfoNode($pricingInfo, $fareInfosByKey);
            if ($primaryFareInfo !== null) {
                $fareBasis = $fareBasis ?: self::attr($primaryFareInfo, 'FareBasis');
                $fareBrand = $fareBrand ?: self::resolveBrandName($primaryFareInfo, $brandsByKey);
            }

            if ($legsCollected) {
                $fareInfos = self::asList(data_get($pricingInfo, 'FareInfo'));
                foreach ($fareInfos as $fareInfo) {
                    if (! is_array($fareInfo)) {
                        continue;
                    }
                    $fareBasis = $fareBasis ?: self::attr($fareInfo, 'FareBasis');
                    $fareBrand = $fareBrand ?: self::resolveBrandName($fareInfo, $brandsByKey);
                }

                continue;
            }

            $flightOptions = self::asList(data_get($pricingInfo, 'FlightOptionsList.FlightOption'));

            foreach ($flightOptions as $flightOption) {
                if (! is_array($flightOption)) {
                    continue;
                }

                $options = self::asList(data_get($flightOption, 'Option'));
                $option = $options[0] ?? null;
                $legSegments = [];

                if (is_array($option)) {
                    $seenRefs = [];
                    $bookingInfos = self::asList(data_get($option, 'BookingInfo'));

                    foreach ($bookingInfos as $bookingInfo) {
                        if (! is_array($bookingInfo)) {
                            continue;
                        }

                        $segmentRef = (string) self::attr($bookingInfo, 'SegmentRef', '');
                        if ($segmentRef === '' || isset($seenRefs[$segmentRef])) {
                            continue;
                        }

                        $segmentNode = $segmentsByKey[$segmentRef] ?? null;
                        if ($segmentNode === null) {
                            continue;
                        }

                        $seenRefs[$segmentRef] = true;
                        $bookingCode = $bookingCode ?: self::attr($bookingInfo, 'BookingCode');
                        $cabinClass = $cabinClass ?: self::attr($bookingInfo, 'CabinClass');

                        if ($seatsAvailable === null) {
                            $bookingCount = self::attr($bookingInfo, 'BookingCount');
                            if ($bookingCount !== null && is_numeric($bookingCount)) {
                                $seatsAvailable = (int) $bookingCount;
                            }
                        }

                        $fareInfoRef = (string) self::attr($bookingInfo, 'FareInfoRef', '');
                        if ($fareInfoRef !== '' && isset($fareInfosByKey[$fareInfoRef])) {
                            $segmentFareInfo = $fareInfosByKey[$fareInfoRef];
                            $fareBasis = $fareBasis ?: self::attr($segmentFareInfo, 'FareBasis');
                            $fareBrand = $fareBrand ?: self::resolveBrandName($segmentFareInfo, $brandsByKey);
                            if ($primaryFareInfo === null) {
                                $primaryFareInfo = $segmentFareInfo;
                            }
                        }

                        $built = self::buildSegment($segmentNode, $bookingCode, $cabinClass);
                        $legSegments[] = $built;
                        if (! isset($rawSegmentKeys[$segmentRef])) {
                            $rawSegmentKeys[$segmentRef] = true;
                            $rawSegments[] = array_merge($segmentNode, ['booking_code' => $bookingCode]);
                        }
                    }

                    if ($legSegments !== []) {
                        $legSignature = self::legSignatureFromSegments($legSegments);
                        if ($legSignature !== '' && isset($seenLegSignatures[$legSignature])) {
                            continue;
                        }
                        if ($legSignature !== '') {
                            $seenLegSignatures[$legSignature] = true;
                        }

                        $elapsed = self::legElapsedMinutes($legSegments);
                        if ($elapsed <= 0) {
                            $elapsed = self::parseTravelTimeMinutes(self::attr($option, 'TravelTime'));
                        }
                        if ($elapsed === null || $elapsed <= 0) {
                            $elapsed = self::sumTravelTime($legSegments);
                        }

                        $legs[] = [
                            'elapsedTime' => $elapsed,
                            'segments' => $legSegments,
                            'filter_axes' => FlightListingMetaBuilder::axisForLegSegments($legSegments),
                        ];
                    }
                }
            }

            if ($legs !== []) {
                $legsCollected = true;
            }

            $fareInfos = self::asList(data_get($pricingInfo, 'FareInfo'));
            foreach ($fareInfos as $fareInfo) {
                if (! is_array($fareInfo)) {
                    continue;
                }
                $fareBasis = $fareBasis ?: self::attr($fareInfo, 'FareBasis');
                $fareBrand = $fareBrand ?: self::resolveBrandName($fareInfo, $brandsByKey);
            }
        }

        if ($legs === []) {
            return null;
        }

        $baggageDetails = TravelportBaggagePresenter::fromFareInfo(
            $primaryFareInfo,
            $primaryPricingInfo,
            $legs,
            $validatingCarrier,
        );
        $displayBrand = $fareBrand ?: ($cabinClass ?: 'Economy');
        $resolvedCabin = FlightCabinPreference::resolveCabinFamily($displayBrand, $bookingCode, $cabinClass);
        $fareRules = TravelportFareRulesPresenter::fromPricing(
            $primaryPricingInfo,
            $primaryFareInfo,
            $legs,
            $displayBrand,
            $resolvedCabin,
        );
        $fareRuleRequest = TravelportFareRulesPresenter::fareRuleRequest(
            $primaryFareInfo,
            $primaryPricingInfo,
            $legs,
        );

        $fareTags = self::inferFareTags($primaryFareInfo, $brandsByKey)['tags'];

        $fareOption = [
            'travelport_pricing_index' => 0,
            'travelport_price_point_key' => $key,
            'totalPrice' => $totalPrice,
            'supplierBasePrice' => $basePrice,
            'supplierTaxes' => $taxes,
            'basePrice' => $basePrice,
            'taxes' => $taxes,
            'currency' => $currency,
            'passenger_fare_lines' => $passengerFareLines,
            'fare_brand' => $displayBrand,
            'fare_basis' => $fareBasis,
            'non_refundable' => $nonRefundable,
            'baggage_notes' => (string) ($baggageDetails['summary'] ?? ''),
            'baggage_details' => $baggageDetails,
            'fare_rules' => $fareRules,
            'travelport_fare_rule' => $fareRuleRequest,
            'fare_tags' => $fareTags,
            'validating_carrier' => $validatingCarrier,
            'cabin_code' => $resolvedCabin,
            'booking_code' => $bookingCode,
            'seats_available' => $seatsAvailable,
        ];

        return [
            'id' => 0,
            'travelport_price_point_key' => $key,
            'travelport_segments' => $rawSegments,
            'supplierPrice' => $totalPrice,
            'supplierBasePrice' => $basePrice,
            'supplierTaxes' => $taxes,
            'basePrice' => $basePrice,
            'taxes' => $taxes,
            'totalPrice' => $totalPrice,
            'currency' => $currency,
            'passenger_fare_lines' => $passengerFareLines,
            'legs' => $legs,
            'supplier' => 'travelport',
            'validating_carrier' => $validatingCarrier,
            'non_refundable' => $nonRefundable,
            'fare_brand' => $displayBrand,
            'baggage_notes' => (string) ($baggageDetails['summary'] ?? ''),
            'baggage_details' => $baggageDetails,
            'fare_rules' => $fareRules,
            'fare_tags' => $fareTags,
            'fare_options' => [$fareOption],
            'listing_meta' => FlightListingMetaBuilder::fromLegs($legs, $totalPrice, ['tags' => $fareTags]),
        ];
    }

    /**
     * @param  array<string, mixed>  $segmentNode
     */
    private static function buildSegment(array $segmentNode, ?string $bookingCode, ?string $cabinClass): array
    {
        $carrier = strtoupper(trim((string) self::attr($segmentNode, 'Carrier', '')));
        $flightNumber = trim((string) self::attr($segmentNode, 'FlightNumber', ''));
        $from = strtoupper(trim((string) self::attr($segmentNode, 'Origin', '')));
        $to = strtoupper(trim((string) self::attr($segmentNode, 'Destination', '')));
        $depRaw = (string) self::attr($segmentNode, 'DepartureTime', '');
        $arrRaw = (string) self::attr($segmentNode, 'ArrivalTime', '');

        $depDateTime = self::parseTravelportDateTime($depRaw);
        $arrDateTime = self::parseTravelportDateTime($arrRaw);
        $depClock = self::localClockFromRaw($depRaw, $depDateTime);
        $arrClock = self::localClockFromRaw($arrRaw, $arrDateTime);
        $elapsed = self::segmentElapsedMinutes($depRaw, $arrRaw, $depDateTime, $arrDateTime, $segmentNode);

        $depLocalDay = self::localDateFromRaw($depRaw, $depDateTime);
        $arrLocalDay = self::localDateFromRaw($arrRaw, $arrDateTime);
        $diffDays = ($depLocalDay !== null && $arrLocalDay !== null)
            ? max(0, $depLocalDay->diffInDays($arrLocalDay, false))
            : max(0, $depDateTime->copy()->startOfDay()->diffInDays($arrDateTime->copy()->startOfDay(), false));
        $mktFlight = $flightNumber;

        return [
            'from' => $from,
            'to' => $to,
            'departure_city' => resolveFlightCityLabel('', $from),
            'arrival_city' => resolveFlightCityLabel('', $to),
            'departure_time' => $depRaw,
            'arrival_time' => $arrRaw,
            'departure_terminal' => self::attr($segmentNode, 'OriginTerminal'),
            'arrival_terminal' => self::attr($segmentNode, 'DestinationTerminal'),
            'carrier' => $carrier,
            'carrier_display' => trim($carrier . ('' !== $mktFlight ? ' ' . $mktFlight : '')),
            'carrier_name' => trim($carrier . ('' !== $mktFlight ? '  -  ' . $mktFlight : '')),
            'flight_number' => $flightNumber,
            'flight_label' => trim($carrier . ('' !== $mktFlight ? ' ' . $mktFlight : '')),
            'operating_carrier' => self::attr($segmentNode, 'OperatingCarrier', $carrier),
            'equipment' => self::attr($segmentNode, 'Equipment'),
            'stop_count' => (int) self::attr($segmentNode, 'NumberOfStops', 0),
            'elapsedTime' => $elapsed,
            'departure_datetime' => $depDateTime->toIso8601String(),
            'arrival_datetime' => $arrDateTime->toIso8601String(),
            'departure_label' => formatFlightSegmentDate($depDateTime),
            'departure_weekday' => $depDateTime->format('D'),
            'arrival_label' => formatFlightSegmentDate($arrDateTime),
            'arrival_weekday' => $arrDateTime->format('D'),
            'next_day_hint' => $diffDays >= 1,
            'departure_clock' => $depClock,
            'arrival_clock' => $arrClock,
            'is_red_eye_segment' => self::clockIsRedEye($depClock),
            'booking_code' => $bookingCode,
            'cabin_code' => $cabinClass,
        ];
    }

    private static function parseTravelportDateTime(string $raw): Carbon
    {
        $raw = trim($raw);
        if ($raw === '') {
            return Carbon::now();
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable $e) {
            return Carbon::now();
        }
    }

    /**
     * Use airport-local wall clock from Travelport ISO timestamps, not app timezone.
     */
    private static function localClockFromRaw(string $raw, Carbon $fallback): string
    {
        if (preg_match('/T(\d{1,2}:\d{2})/', trim($raw), $matches)) {
            return self::normalizeClock($matches[1]);
        }

        return self::normalizeClock($fallback->format('H:i'));
    }

    private static function localDateFromRaw(string $raw, Carbon $fallback): ?Carbon
    {
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', trim($raw), $matches)) {
            try {
                return Carbon::parse($matches[1])->startOfDay();
            } catch (\Throwable $e) {
                return null;
            }
        }

        try {
            return $fallback->copy()->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $segmentNode
     */
    private static function segmentElapsedMinutes(
        string $depRaw,
        string $arrRaw,
        Carbon $depDateTime,
        Carbon $arrDateTime,
        array $segmentNode,
    ): int {
        $elapsedFromDates = 0;

        if ($depRaw !== '' && $arrRaw !== '') {
            try {
                $elapsedFromDates = max(0, (int) $depDateTime->diffInMinutes($arrDateTime, false));
            } catch (\Throwable $e) {
                $elapsedFromDates = 0;
            }
        }

        $flightTime = (int) self::attr($segmentNode, 'FlightTime', 0);

        if ($elapsedFromDates > 0) {
            return max(15, $elapsedFromDates);
        }

        if ($flightTime > 0) {
            return max(15, $flightTime);
        }

        return 15;
    }

    private static function clockIsRedEye(string $clock): bool
    {
        $parts = explode(':', $clock);
        $h = (int) ($parts[0] ?? 0);

        return $h >= 22 || $h < 6;
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     */
    private static function sumTravelTime(array $segments): int
    {
        return array_sum(array_map(static fn ($s) => (int) ($s['elapsedTime'] ?? 0), $segments));
    }

    /**
     * @param  array<string, mixed>  $pricePoint
     */
    private static function extractTotalPrice(array $pricePoint): ?float
    {
        foreach ([
            self::attr($pricePoint, 'TotalPrice'),
            self::attr($pricePoint, 'ApproximateTotalPrice'),
            self::attr(data_get($pricePoint, 'AirPricingInfo'), 'TotalPrice'),
        ] as $raw) {
            $money = self::parseMoneyValue($raw);
            if (($money['amount'] ?? null) !== null) {
                return $money['amount'];
            }
        }

        return null;
    }

    /**
     * @return array{amount: ?float, currency: ?string}
     */
    private static function parseMoneyValue(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return ['amount' => null, 'currency' => null];
        }

        if (is_numeric($raw)) {
            return ['amount' => round((float) $raw, 2), 'currency' => null];
        }

        $text = trim((string) $raw);
        if ($text === '') {
            return ['amount' => null, 'currency' => null];
        }

        if (preg_match('/^([A-Z]{3})([\d.,]+)$/i', $text, $matches)) {
            return [
                'amount' => round((float) str_replace(',', '', $matches[2]), 2),
                'currency' => strtoupper($matches[1]),
            ];
        }

        if (preg_match('/^([\d.,]+)\s*([A-Z]{3})$/i', $text, $matches)) {
            return [
                'amount' => round((float) str_replace(',', '', $matches[1]), 2),
                'currency' => strtoupper($matches[2]),
            ];
        }

        if (preg_match('/[\d.]/', $text)) {
            $amount = (float) preg_replace('/[^0-9.]/', '', $text);

            return ['amount' => $amount > 0 ? round($amount, 2) : null, 'currency' => null];
        }

        return ['amount' => null, 'currency' => null];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function indexByKey(mixed $nodes): array
    {
        $indexed = [];
        foreach (self::asList($nodes) as $node) {
            if (! is_array($node)) {
                continue;
            }
            $key = (string) self::attr($node, 'Key', '');
            if ($key !== '') {
                $indexed[$key] = $node;
            }
        }

        return $indexed;
    }

    /**
     * @return list<mixed>
     */
    private static function asList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (! is_array($value)) {
            return [$value];
        }

        if ($value === []) {
            return [];
        }

        if (array_is_list($value)) {
            return $value;
        }

        return [$value];
    }

    private static function attr(mixed $node, string $name, mixed $default = null): mixed
    {
        if (! is_array($node)) {
            return $default;
        }

        if (isset($node['@attributes'][$name])) {
            return $node['@attributes'][$name];
        }

        if (array_key_exists($name, $node)) {
            return $node[$name];
        }

        if (array_key_exists('@' . $name, $node)) {
            return $node['@' . $name];
        }

        return $default;
    }

    /**
     * @param  list<array<string, mixed>>  $cards
     * @return list<array<string, mixed>>
     */
    private static function groupCardsByRouting(array $cards): array
    {
        $grouped = [];

        foreach ($cards as $card) {
            $signature = self::routingSignature($card);

            if (! isset($grouped[$signature])) {
                $grouped[$signature] = $card;

                continue;
            }

            $existing = $grouped[$signature];
            $mergedOptions = self::mergeFareOptions(
                $existing['fare_options'] ?? [],
                $card['fare_options'] ?? [],
            );

            $existing['fare_options'] = $mergedOptions;
            $existing['totalPrice'] = (float) ($mergedOptions[0]['totalPrice'] ?? $existing['totalPrice'] ?? 0);
            $existing['supplierPrice'] = $existing['totalPrice'];

            $cheapest = $mergedOptions[0] ?? [];
            $existing['fare_brand'] = $cheapest['fare_brand'] ?? $existing['fare_brand'] ?? null;
            $existing['baggage_details'] = $cheapest['baggage_details'] ?? $existing['baggage_details'] ?? [];
            $existing['baggage_notes'] = $cheapest['baggage_notes'] ?? $existing['baggage_notes'] ?? '';
            $existing['fare_rules'] = $cheapest['fare_rules'] ?? $existing['fare_rules'] ?? [];
            $existing['non_refundable'] = (bool) ($cheapest['non_refundable'] ?? $existing['non_refundable'] ?? false);
            $existing['fare_tags'] = $cheapest['fare_tags'] ?? $existing['fare_tags'] ?? ['published'];
            $existing['listing_meta'] = FlightListingMetaBuilder::fromLegs(
                $existing['legs'] ?? [],
                $existing['totalPrice'],
                ['tags' => $existing['fare_tags']],
            );

            $grouped[$signature] = $existing;
        }

        return array_values($grouped);
    }

    /**
     * @param  array<string, mixed>  $card
     */
    public static function routingSignature(array $card): string
    {
        $parts = [];

        foreach ($card['legs'] ?? [] as $leg) {
            foreach ($leg['segments'] ?? [] as $segment) {
                if (! is_array($segment)) {
                    continue;
                }

                $parts[] = implode(':', [
                    strtoupper((string) ($segment['carrier'] ?? '')),
                    trim((string) ($segment['flight_number'] ?? '')),
                    (string) ($segment['departure_clock'] ?? ''),
                    strtoupper((string) ($segment['from'] ?? '')),
                    strtoupper((string) ($segment['to'] ?? '')),
                ]);
            }
        }

        return implode('|', $parts);
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     */
    private static function legSignatureFromSegments(array $segments): string
    {
        $parts = [];

        foreach ($segments as $segment) {
            if (! is_array($segment)) {
                continue;
            }

            $parts[] = implode(':', [
                strtoupper((string) ($segment['carrier'] ?? '')),
                trim((string) ($segment['flight_number'] ?? '')),
                (string) ($segment['departure_clock'] ?? ''),
                strtoupper((string) ($segment['from'] ?? '')),
                strtoupper((string) ($segment['to'] ?? '')),
            ]);
        }

        return implode('|', $parts);
    }

    /**
     * @param  list<array<string, mixed>>  $left
     * @param  list<array<string, mixed>>  $right
     * @return list<array<string, mixed>>
     */
    private static function mergeFareOptions(array $left, array $right): array
    {
        $merged = [];
        $seen = [];

        foreach (array_merge($left, $right) as $option) {
            if (! is_array($option)) {
                continue;
            }

            // Travelport may return multiple AirPricePoint keys for the same published fare.
            // Dedupe on visible fare identity, not the opaque price-point key.
            $dedupeKey = implode('|', [
                strtoupper((string) ($option['fare_basis'] ?? '')),
                strtoupper((string) ($option['fare_brand'] ?? '')),
                number_format((float) ($option['totalPrice'] ?? 0), 2, '.', ''),
                implode(',', array_values(array_unique(array_map(
                    static fn ($tag): string => strtolower((string) $tag),
                    is_array($option['fare_tags'] ?? null) ? $option['fare_tags'] : [],
                )))),
                strtoupper((string) ($option['booking_code'] ?? '')),
                strtoupper((string) ($option['cabin_code'] ?? '')),
                ($option['non_refundable'] ?? false) ? '1' : '0',
            ]);

            if (isset($seen[$dedupeKey])) {
                continue;
            }

            $seen[$dedupeKey] = true;
            $merged[] = $option;
        }

        usort($merged, static fn (array $a, array $b): int => ((float) ($a['totalPrice'] ?? 0)) <=> ((float) ($b['totalPrice'] ?? 0)));

        foreach ($merged as $index => &$option) {
            $option['travelport_pricing_index'] = $index;
        }
        unset($option);

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $pricingInfo
     * @param  array<string, array<string, mixed>>  $fareInfosByKey
     */
    private static function resolveFareInfoNode(array $pricingInfo, array $fareInfosByKey): ?array
    {
        foreach (self::asList(data_get($pricingInfo, 'FareInfoRef')) as $fareInfoRef) {
            if (! is_array($fareInfoRef)) {
                continue;
            }

            $key = (string) self::attr($fareInfoRef, 'Key', '');
            if ($key !== '' && isset($fareInfosByKey[$key])) {
                return $fareInfosByKey[$key];
            }
        }

        $singleRef = data_get($pricingInfo, 'FareInfoRef');
        if (is_array($singleRef)) {
            $key = (string) self::attr($singleRef, 'Key', '');
            if ($key !== '' && isset($fareInfosByKey[$key])) {
                return $fareInfosByKey[$key];
            }
        }

        return null;
    }

    /**
     * Branded fares returned with rich content are treated as NDC retail in listings.
     *
     * @param  array<string, mixed>|null  $fareInfo
     * @param  array<string, array<string, mixed>>  $brandsByKey
     * @return array{tags: list<string>}
     */
    private static function inferFareTags(?array $fareInfo, array $brandsByKey): array
    {
        $tags = ['published'];
        $brand = $fareInfo !== null ? self::resolveBrandRecord($fareInfo, $brandsByKey) : null;

        if ($brand !== null
            && strtolower((string) self::attr($brand, 'BrandedDetailsAvailable', '')) === 'true') {
            $tags[] = 'ndc';
        } else {
            $tags[] = 'gds';
        }

        return ['tags' => array_values(array_unique($tags))];
    }

    /**
     * @param  array<string, mixed>  $fareInfo
     * @param  array<string, array<string, mixed>>  $brandsByKey
     * @return array<string, mixed>|null
     */
    private static function resolveBrandRecord(array $fareInfo, array $brandsByKey): ?array
    {
        $brandNode = data_get($fareInfo, 'Brand');
        if (! is_array($brandNode)) {
            return null;
        }

        $brandKey = (string) self::attr($brandNode, 'Key', '');
        if ($brandKey !== '' && isset($brandsByKey[$brandKey])) {
            return $brandsByKey[$brandKey];
        }

        $brandId = (string) self::attr($brandNode, 'BrandID', '');
        if ($brandId === '') {
            return null;
        }

        foreach ($brandsByKey as $brand) {
            if (! is_array($brand)) {
                continue;
            }

            if ((string) self::attr($brand, 'BrandID', '') === $brandId) {
                return $brand;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $fareInfo
     * @param  array<string, array<string, mixed>>  $brandsByKey
     */
    private static function resolveBrandName(array $fareInfo, array $brandsByKey): ?string
    {
        $brand = self::resolveBrandRecord($fareInfo, $brandsByKey);
        if ($brand === null) {
            return null;
        }

        $name = trim((string) self::attr($brand, 'Name', ''));

        return $name !== '' ? $name : null;
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     */
    private static function legElapsedMinutes(array $segments): int
    {
        if ($segments === []) {
            return 0;
        }

        $first = $segments[0];
        $last = $segments[array_key_last($segments)];

        try {
            $departure = Carbon::parse((string) ($first['departure_datetime'] ?? ''));
            $arrival = Carbon::parse((string) ($last['arrival_datetime'] ?? ''));

            return max(1, (int) $departure->diffInMinutes($arrival, false));
        } catch (\Throwable $e) {
            return self::sumTravelTime($segments);
        }
    }

    private static function parseTravelTimeMinutes(?string $isoDuration): ?int
    {
        $isoDuration = trim((string) ($isoDuration ?? ''));
        if ($isoDuration === '') {
            return null;
        }

        if (! preg_match('/P(?:(\d+)D)?T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/i', $isoDuration, $matches)) {
            return null;
        }

        $days = (int) ($matches[1] ?? 0);
        $hours = (int) ($matches[2] ?? 0);
        $minutes = (int) ($matches[3] ?? 0);
        $seconds = (int) ($matches[4] ?? 0);
        $total = ($days * 24 * 60) + ($hours * 60) + $minutes;

        if ($seconds >= 30) {
            $total++;
        }

        return $total > 0 ? $total : null;
    }

    private static function normalizeClock(string $clock): string
    {
        $clock = trim($clock);
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $clock, $matches)) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        if (preg_match('/^(\d{1,2})$/', $clock, $matches)) {
            return sprintf('%02d:00', (int) $matches[1]);
        }

        return $clock;
    }

}
