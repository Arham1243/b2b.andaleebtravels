<?php

namespace App\Support\Travelport;

use App\Support\FlightListingMetaBuilder;
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
        $pricePoints = self::asList(data_get($rsp, 'AirPricePointList.AirPricePoint'));

        $results = [];

        foreach ($pricePoints as $pricePoint) {
            if (! is_array($pricePoint)) {
                continue;
            }

            $card = self::buildCard($pricePoint, $segmentsByKey, $searchData);
            if ($card !== null) {
                $results[] = $card;
            }
        }

        usort($results, static fn (array $a, array $b): int => ((float) ($a['totalPrice'] ?? 0)) <=> ((float) ($b['totalPrice'] ?? 0)));

        return $results;
    }

    /**
     * @param  array<string, mixed>  $pricePoint
     * @param  array<string, array<string, mixed>>  $segmentsByKey
     * @param  array<string, mixed>  $searchData
     */
    private static function buildCard(array $pricePoint, array $segmentsByKey, array $searchData): ?array
    {
        $key = (string) self::attr($pricePoint, 'Key', '');
        $totalPrice = self::extractTotalPrice($pricePoint);
        if ($totalPrice === null || $totalPrice <= 0) {
            return null;
        }

        $currency = strtoupper((string) (self::attr($pricePoint, 'Currency', '') ?: self::attr(data_get($pricePoint, 'TotalPrice'), 'CurrencyCode', 'AED')));

        $pricingInfos = self::asList(data_get($pricePoint, 'AirPricingInfo'));
        $legs = [];
        $rawSegments = [];
        $validatingCarrier = null;
        $fareBasis = null;
        $bookingCode = null;
        $cabinClass = null;

        foreach ($pricingInfos as $pricingInfo) {
            if (! is_array($pricingInfo)) {
                continue;
            }

            $validatingCarrier = $validatingCarrier ?: self::attr($pricingInfo, 'PlatingCarrier');
            $flightOptions = self::asList(data_get($pricingInfo, 'FlightOptionsList.FlightOption'));

            foreach ($flightOptions as $flightOption) {
                if (! is_array($flightOption)) {
                    continue;
                }

                $options = self::asList(data_get($flightOption, 'Option'));
                $legSegments = [];

                foreach ($options as $option) {
                    if (! is_array($option)) {
                        continue;
                    }

                    $bookingInfos = self::asList(data_get($option, 'BookingInfo'));
                    foreach ($bookingInfos as $bookingInfo) {
                        if (! is_array($bookingInfo)) {
                            continue;
                        }

                        $segmentRef = (string) self::attr($bookingInfo, 'SegmentRef', '');
                        $segmentNode = $segmentsByKey[$segmentRef] ?? null;
                        if ($segmentNode === null) {
                            continue;
                        }

                        $bookingCode = $bookingCode ?: self::attr($bookingInfo, 'BookingCode');
                        $cabinClass = $cabinClass ?: self::attr($bookingInfo, 'CabinClass');

                        $built = self::buildSegment($segmentNode, $bookingCode, $cabinClass);
                        $legSegments[] = $built;
                        $rawSegments[] = array_merge($segmentNode, ['booking_code' => $bookingCode]);
                    }
                }

                if ($legSegments !== []) {
                    $elapsed = array_sum(array_map(static fn ($s) => (int) ($s['elapsedTime'] ?? 0), $legSegments));
                    $legs[] = [
                        'elapsedTime' => $elapsed > 0 ? $elapsed : self::sumTravelTime($legSegments),
                        'segments' => $legSegments,
                        'filter_axes' => FlightListingMetaBuilder::axisForLegSegments($legSegments),
                    ];
                }
            }

            $fareInfos = self::asList(data_get($pricingInfo, 'FareInfo'));
            foreach ($fareInfos as $fareInfo) {
                if (! is_array($fareInfo)) {
                    continue;
                }
                $fareBasis = $fareBasis ?: self::attr($fareInfo, 'FareBasis');
            }
        }

        if ($legs === []) {
            return null;
        }

        $fareOption = [
            'travelport_pricing_index' => 0,
            'totalPrice' => $totalPrice,
            'supplierBasePrice' => null,
            'supplierTaxes' => null,
            'basePrice' => null,
            'taxes' => null,
            'currency' => $currency,
            'fare_brand' => $cabinClass ?: 'Economy',
            'fare_basis' => $fareBasis,
            'non_refundable' => false,
            'baggage_notes' => '',
            'baggage_details' => [],
            'fare_rules' => [],
            'fare_tags' => ['published'],
            'validating_carrier' => $validatingCarrier,
            'cabin_code' => $cabinClass,
            'booking_code' => $bookingCode,
        ];

        return [
            'id' => 0,
            'travelport_price_point_key' => $key,
            'travelport_segments' => $rawSegments,
            'supplierPrice' => $totalPrice,
            'supplierBasePrice' => null,
            'supplierTaxes' => null,
            'basePrice' => null,
            'taxes' => null,
            'totalPrice' => $totalPrice,
            'currency' => $currency,
            'legs' => $legs,
            'supplier' => 'travelport',
            'validating_carrier' => $validatingCarrier,
            'non_refundable' => false,
            'fare_brand' => $cabinClass ?: 'Economy',
            'baggage_notes' => '',
            'baggage_details' => [],
            'fare_rules' => [],
            'fare_tags' => ['published'],
            'fare_options' => [$fareOption],
            'listing_meta' => FlightListingMetaBuilder::fromLegs($legs, $totalPrice, ['tags' => ['published']]),
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
        $depClock = $depDateTime->format('H:i');
        $arrClock = $arrDateTime->format('H:i');
        $elapsed = (int) self::attr($segmentNode, 'FlightTime', 0);
        if ($elapsed <= 0) {
            $elapsed = max(15, (int) $depDateTime->diffInMinutes($arrDateTime, false));
        }

        $diffDays = max(0, $depDateTime->copy()->startOfDay()->diffInDays($arrDateTime->copy()->startOfDay(), false));
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
        $direct = self::attr($pricePoint, 'TotalPrice');
        if (is_numeric($direct)) {
            return round((float) $direct, 2);
        }

        $node = data_get($pricePoint, 'TotalPrice');
        if (is_array($node)) {
            foreach (['Amount', 'Total', 'Base'] as $key) {
                $val = self::attr($node, $key);
                if (is_numeric($val)) {
                    return round((float) $val, 2);
                }
            }
        }

        $approx = self::attr($pricePoint, 'ApproximateTotalPrice');
        if (is_numeric($approx)) {
            return round((float) $approx, 2);
        }

        return null;
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
}
