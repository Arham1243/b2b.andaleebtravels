<?php

namespace App\Support\Travelport;

use App\Support\FlightCabinPreference;
use App\Support\FlightPassengerFareLinesPresenter;

class TravelportAirPricePresenter
{
    /**
     * @param  array<string, mixed>|null  $parsed
     * @param  array<string, mixed>  $searchData
     * @param  list<array<string, mixed>>  $legs
     * @param  list<string>|null  $fareTagsOverride
     * @return list<array<string, mixed>>
     */
    public static function toFareOptions(
        ?array $parsed,
        array $searchData,
        array $legs,
        ?array $fareTagsOverride = null,
    ): array {
        if (! is_array($parsed)) {
            return [];
        }

        $solutions = self::asList(data_get($parsed, 'Body.AirPriceRsp.AirPriceResult.AirPricingSolution'));
        $options = [];

        foreach ($solutions as $solution) {
            if (! is_array($solution)) {
                continue;
            }

            $option = self::buildFareOption($solution, $legs, $searchData, $fareTagsOverride);
            if ($option !== null) {
                $options[] = $option;
            }
        }

        usort($options, static fn (array $a, array $b): int => ((float) ($a['totalPrice'] ?? 0)) <=> ((float) ($b['totalPrice'] ?? 0)));

        foreach ($options as $index => &$option) {
            $option['travelport_pricing_index'] = $index;
        }
        unset($option);

        return self::filterBySearchCabins($options, $searchData);
    }

    /**
     * @param  array<string, mixed>  $solution
     * @param  list<array<string, mixed>>  $legs
     * @param  array<string, mixed>  $searchData
     * @param  list<string>|null  $fareTagsOverride
     * @return array<string, mixed>|null
     */
    private static function buildFareOption(
        array $solution,
        array $legs,
        array $searchData,
        ?array $fareTagsOverride = null,
    ): ?array {
        $solutionKey = (string) self::attr($solution, 'Key', '');
        $totalMoney = self::parseMoney(self::attr($solution, 'TotalPrice') ?: self::attr($solution, 'ApproximateTotalPrice'));
        if (($totalMoney['amount'] ?? 0) <= 0) {
            return null;
        }

        $pricingInfos = self::asList($solution['AirPricingInfo'] ?? null);
        $pricingInfo = $pricingInfos[0] ?? null;
        if (! is_array($pricingInfo)) {
            return null;
        }

        $fareInfo = self::asList($pricingInfo['FareInfo'] ?? null)[0] ?? null;
        if (! is_array($fareInfo)) {
            return null;
        }

        $brandNode = is_array($fareInfo['Brand'] ?? null) ? $fareInfo['Brand'] : null;
        $brandName = $brandNode !== null
            ? (string) (self::attr($brandNode, 'Name') ?: self::attr($brandNode, 'BrandID'))
            : '';
        $fareBasis = (string) self::attr($fareInfo, 'FareBasis', '');
        $bookingCode = $fareBasis !== '' ? strtoupper(substr($fareBasis, 0, 1)) : '';
        $cabinClass = FlightCabinPreference::resolveCabinFamily($brandName, $bookingCode);
        $validatingCarrier = (string) self::attr($pricingInfo, 'PlatingCarrier', '');

        $passengerFareLines = FlightPassengerFareLinesPresenter::fromTravelportPricingInfos($pricingInfos, $searchData);
        $passengerFareTotals = FlightPassengerFareLinesPresenter::aggregateTotals($passengerFareLines);
        $baseMoney = self::parseMoney(self::attr($pricingInfo, 'BasePrice'));
        $taxMoney = self::parseMoney(self::attr($pricingInfo, 'Taxes'));
        if (($passengerFareTotals['base'] ?? 0) > 0) {
            $baseMoney['amount'] = $passengerFareTotals['base'];
        }
        if (($passengerFareTotals['tax'] ?? 0) > 0) {
            $taxMoney['amount'] = $passengerFareTotals['tax'];
        }
        $currency = $totalMoney['currency'] ?? $baseMoney['currency'] ?? 'AED';
        $nonRefundable = strtolower((string) self::attr($pricingInfo, 'Refundable', 'true')) === 'false';

        $displayBrand = $brandName !== '' ? str_replace('  ', ' ', ucwords(strtolower($brandName))) : ($cabinClass ?: 'Economy');
        $displayBrand = self::normalizeBrandLabel($displayBrand);

        $baggageDetails = TravelportBaggagePresenter::fromFareInfo($fareInfo, $pricingInfo, $legs, $validatingCarrier);
        $fareRules = TravelportFareRulesPresenter::fromPricing(
            $pricingInfo,
            $fareInfo,
            $legs,
            $displayBrand,
            $cabinClass,
        );
        $fareRuleRequest = TravelportFareRulesPresenter::fareRuleRequest($fareInfo, $pricingInfo, $legs);

        $hostToken = is_string($solution['HostToken'] ?? null) ? trim($solution['HostToken']) : '';
        $fareTags = $fareTagsOverride ?? self::inferFareTags($brandNode, $hostToken);

        return [
            'travelport_pricing_index' => 0,
            'travelport_price_point_key' => $solutionKey,
            'travelport_air_price_solution' => true,
            'travelport_host_token' => $hostToken !== '' ? $hostToken : null,
            'totalPrice' => (float) $totalMoney['amount'],
            'supplierBasePrice' => $baseMoney['amount'],
            'supplierTaxes' => $taxMoney['amount'],
            'basePrice' => $baseMoney['amount'],
            'taxes' => $taxMoney['amount'],
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
            'cabin_code' => $cabinClass,
            'booking_code' => $bookingCode,
            'seats_available' => null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @param  array<string, mixed>  $searchData
     * @return list<array<string, mixed>>
     */
    private static function filterBySearchCabins(array $options, array $searchData): array
    {
        return array_values(array_filter(
            $options,
            static fn (array $option): bool => FlightCabinPreference::fareMatchesSearch(
                $option,
                $searchData['onward_cabin_class'] ?? 'Economy',
                $searchData['return_cabin_class'] ?? null,
                (string) ($searchData['trip_type'] ?? 'one_way'),
            ),
        ));
    }

    private static function normalizeBrandLabel(string $brand): string
    {
        return (string) preg_replace('/\bFlexplus\b/i', 'Flex Plus', $brand);
    }

    /**
     * AirPrice FareFamily host tokens (GFB…) are GDS booking paths even when brand content is rich.
     *
     * @return list<string>
     */
    private static function inferFareTags(?array $brandNode, string $hostToken = ''): array
    {
        $tags = ['published'];

        if ($hostToken !== '' && str_starts_with(strtoupper($hostToken), 'GFB')) {
            $tags[] = 'gds';

            return $tags;
        }

        if ($brandNode === null) {
            $tags[] = 'gds';

            return $tags;
        }

        if (strtolower((string) self::attr($brandNode, 'BrandedDetailsAvailable', '')) === 'true') {
            $tags[] = 'ndc';

            return $tags;
        }

        $tags[] = 'gds';

        return $tags;
    }

    /**
     * @return array{amount: ?float, currency: ?string}
     */
    private static function parseMoney(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return ['amount' => null, 'currency' => null];
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

        return ['amount' => null, 'currency' => null];
    }

    private static function attr(mixed $node, string $name, mixed $default = null): mixed
    {
        if (! is_array($node)) {
            return $default;
        }

        if (isset($node['@attributes'][$name])) {
            return $node['@attributes'][$name];
        }

        return $node[$name] ?? $default;
    }

    /**
     * @return list<mixed>
     */
    private static function asList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        if ($value === []) {
            return [];
        }

        return array_is_list($value) ? $value : [$value];
    }
}
