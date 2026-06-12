<?php

namespace App\Support\Travelport;

/**
 * Extract structured pricing data from Travelport airPrice raw XML for hold/booking.
 */
class TravelportAirPriceParser
{
    /**
     * @return array<string, mixed>
     */
    public static function extract(string $rawXml, string $requestedBookingCode = ''): array
    {
        $pd = [
            'provider_code' => '',
            'carrier' => '',
            'solution_key' => '',
            'total_price' => '',
            'base_price' => '',
            'taxes' => '',
            'pricing_info_key' => '',
            'pricing_method' => '',
            'latest_ticketing_time' => '',
            'taxes_xml' => '',
            'host_tokens' => [],
            'segments' => [],
            'fare_infos' => [],
            'booking_infos' => [],
        ];

        if ($rawXml === '') {
            return $pd;
        }

        $solutionXml = self::firstPricingSolutionXml($rawXml);
        if (preg_match('/<air:AirPricingSolution[^>]+Key="([^"]+)"[^>]+TotalPrice="([^"]+)"/i', $solutionXml, $m)) {
            $pd['solution_key'] = $m[1];
            $pd['total_price'] = $m[2];
        } elseif (preg_match('/<air:AirPricingSolution[^>]+TotalPrice="([^"]+)"[^>]+Key="([^"]+)"/i', $solutionXml, $m)) {
            $pd['total_price'] = $m[1];
            $pd['solution_key'] = $m[2];
        }

        if (preg_match('/<air:AirPricingInfo[^>]+Key="([^"]+)"/i', $solutionXml, $m)) {
            $pd['pricing_info_key'] = $m[1];
        }
        if (preg_match('/<air:AirPricingInfo[^>]+PricingMethod="([^"]+)"/i', $solutionXml, $m)) {
            $pd['pricing_method'] = $m[1];
        }
        if (preg_match('/<air:AirPricingInfo[^>]+BasePrice="([^"]+)"/i', $solutionXml, $m)) {
            $pd['base_price'] = $m[1];
        }
        if (preg_match('/<air:AirPricingInfo[^>]+Taxes="([^"]+)"/i', $solutionXml, $m)) {
            $pd['taxes'] = $m[1];
        }

        preg_match_all('/LatestTicketingTime="([^"]+)"/i', $solutionXml, $lttMatches);
        if (! empty($lttMatches[1])) {
            $pd['latest_ticketing_time'] = end($lttMatches[1]);
        }

        $segmentsByKey = [];
        preg_match_all('/<air:AirSegment\s+([^>]+?)\/?>/i', $rawXml, $segMatches, PREG_SET_ORDER);
        foreach ($segMatches as $match) {
            $attrs = self::parseAttributeString($match[1]);
            $key = $attrs['Key'] ?? '';
            if ($key === '') {
                continue;
            }
            $segmentsByKey[$key] = [
                'key' => $key,
                'group' => $attrs['Group'] ?? '0',
                'provider_code' => $attrs['ProviderCode'] ?? '1G',
                'carrier' => $attrs['Carrier'] ?? '',
                'flight_number' => $attrs['FlightNumber'] ?? '',
                'origin' => $attrs['Origin'] ?? '',
                'destination' => $attrs['Destination'] ?? '',
                'dep_time' => $attrs['DepartureTime'] ?? '',
                'arr_time' => $attrs['ArrivalTime'] ?? '',
                'flight_time' => $attrs['FlightTime'] ?? '',
                'travel_time' => $attrs['TravelTime'] ?? '',
                'equipment' => $attrs['Equipment'] ?? '320',
                'booking_code' => $attrs['ClassOfService'] ?? '',
            ];
            if ($pd['provider_code'] === '') {
                $pd['provider_code'] = $segmentsByKey[$key]['provider_code'];
            }
            if ($pd['carrier'] === '') {
                $pd['carrier'] = $segmentsByKey[$key]['carrier'];
            }
        }

        preg_match_all('/<air:BookingInfo\s+([^>]+?)\/?>/i', $solutionXml, $bookingMatches, PREG_SET_ORDER);
        foreach ($bookingMatches as $match) {
            $attrs = self::parseAttributeString($match[1]);
            $bookingCode = $attrs['BookingCode'] ?? '';
            if ($requestedBookingCode !== '' && strtoupper($bookingCode) !== strtoupper($requestedBookingCode)) {
                continue;
            }
            $pd['booking_infos'][] = [
                'booking_code' => $bookingCode,
                'cabin_class' => $attrs['CabinClass'] ?? '',
                'fare_info_ref' => $attrs['FareInfoRef'] ?? '',
                'segment_ref' => $attrs['SegmentRef'] ?? '',
                'host_token_ref' => $attrs['HostTokenRef'] ?? '',
            ];
        }

        // Do not silently substitute a different booking class when the shopper selected one.
        if ($pd['booking_infos'] === [] && $requestedBookingCode !== '' && ! empty($bookingMatches)) {
            return $pd;
        }

        if ($pd['booking_infos'] === [] && ! empty($bookingMatches)) {
            foreach ($bookingMatches as $match) {
                $attrs = self::parseAttributeString($match[1]);
                $pd['booking_infos'][] = [
                    'booking_code' => $attrs['BookingCode'] ?? '',
                    'cabin_class' => $attrs['CabinClass'] ?? '',
                    'fare_info_ref' => $attrs['FareInfoRef'] ?? '',
                    'segment_ref' => $attrs['SegmentRef'] ?? '',
                    'host_token_ref' => $attrs['HostTokenRef'] ?? '',
                ];
            }
        }

        preg_match_all('/<air:FareInfo\s+([^>]+?)(?:\/>|>(.*?)<\/air:FareInfo>)/is', $solutionXml, $fareMatches, PREG_SET_ORDER);
        $fareInfosByKey = [];
        foreach ($fareMatches as $match) {
            $attrs = self::parseAttributeString($match[1]);
            $key = $attrs['Key'] ?? '';
            if ($key === '') {
                continue;
            }
            $inner = $match[2] ?? '';
            $fareRuleKey = '';
            if (preg_match('/<air:FareRuleKey[^>]*>([^<]+)<\/air:FareRuleKey>/i', $inner, $fr)) {
                $fareRuleKey = trim($fr[1]);
            } elseif (preg_match('/<air:FareRuleKey[^>]*>([^<]+)<\/air:FareRuleKey>/i', $solutionXml, $fr)) {
                $fareRuleKey = trim($fr[1]);
            }
            $fareInfosByKey[$key] = [
                'key' => $key,
                'fare_basis' => $attrs['FareBasis'] ?? '',
                'passenger_type_code' => $attrs['PassengerTypeCode'] ?? 'ADT',
                'origin' => $attrs['Origin'] ?? '',
                'destination' => $attrs['Destination'] ?? '',
                'departure_date' => $attrs['DepartureDate'] ?? '',
                'effective_date' => $attrs['EffectiveDate'] ?? '',
                'fare_rule_key' => $fareRuleKey,
            ];
        }

        $neededFareRefs = array_unique(array_filter(array_column($pd['booking_infos'], 'fare_info_ref')));
        foreach ($neededFareRefs as $ref) {
            if (isset($fareInfosByKey[$ref])) {
                $pd['fare_infos'][] = $fareInfosByKey[$ref];
            }
        }
        if ($pd['fare_infos'] === []) {
            $pd['fare_infos'] = array_values($fareInfosByKey);
        }

        $segmentRefs = array_unique(array_filter(array_column($pd['booking_infos'], 'segment_ref')));
        if ($segmentRefs === []) {
            preg_match_all('/<air:AirSegmentRef\s+Key="([^"]+)"/i', $solutionXml, $refMatches);
            $segmentRefs = array_values(array_unique($refMatches[1] ?? []));
        }

        $addedSegmentKeys = [];
        foreach ($segmentRefs as $refKey) {
            if ($refKey === '' || isset($addedSegmentKeys[$refKey]) || ! isset($segmentsByKey[$refKey])) {
                continue;
            }
            $seg = $segmentsByKey[$refKey];
            foreach ($pd['booking_infos'] as $bi) {
                if (($bi['segment_ref'] ?? '') === $refKey && ($bi['booking_code'] ?? '') !== '') {
                    $seg['booking_code'] = $bi['booking_code'];
                    break;
                }
            }
            $pd['segments'][] = $seg;
            $addedSegmentKeys[$refKey] = true;
        }

        if ($pd['segments'] === []) {
            if ($requestedBookingCode !== '') {
                return $pd;
            }

            $pd['segments'] = array_values($segmentsByKey);
        }

        preg_match_all('/<(?:[\w-]+:)?HostToken\s+Key="([^"]+)">([^<]+)<\/(?:[\w-]+:)?HostToken>/i', $solutionXml, $htMatches, PREG_SET_ORDER);
        if ($htMatches === []) {
            preg_match_all('/<common_v52_0:HostToken\s+Key="([^"]+)">([^<]+)<\/common_v52_0:HostToken>/i', $solutionXml, $htMatches, PREG_SET_ORDER);
        }
        $neededHostRefs = array_unique(array_filter(array_column($pd['booking_infos'], 'host_token_ref')));
        foreach ($htMatches as $ht) {
            if ($neededHostRefs !== [] && ! in_array($ht[1], $neededHostRefs, true)) {
                continue;
            }
            $pd['host_tokens'][] = [
                'key' => $ht[1],
                'value' => trim($ht[2]),
            ];
        }

        $taxInfoXml = '';
        preg_match_all('/<air:TaxInfo[^>]+\/>/i', $solutionXml, $taxMatches);
        $seenCategories = [];
        foreach ($taxMatches[0] as $taxTag) {
            preg_match('/Category="([^"]+)"/i', $taxTag, $cat);
            $category = $cat[1] ?? '';
            if ($category && ! in_array($category, $seenCategories, true)) {
                $seenCategories[] = $category;
                $taxInfoXml .= "\n                    " . str_replace('air:', '', $taxTag);
            }
        }
        $pd['taxes_xml'] = $taxInfoXml;
        $pd['segments'] = self::dedupeSegmentsByKey($pd['segments']);

        return $pd;
    }

    /**
     * Build AirAdd payload for Travelport's Store Price flow: paste the
     * AirPricingInfo (+ HostToken) returned from airPrice on the held PNR.
     *
     * @param  array<string, string>  $travelerKeyMap  request key => GDS key
     * @param  array<string, string>  $segmentKeyMap  airPrice segment key => held PNR segment key
     * @param  list<string>  $bookingTravelerRefs  GDS BookingTraveler keys on the held PNR
     */
    public static function extractStorePriceAddXml(
        string $rawXml,
        array $travelerKeyMap = [],
        array $segmentKeyMap = [],
        array $bookingTravelerRefs = [],
    ): string {
        $solutionXml = self::firstPricingSolutionXml($rawXml);
        if ($solutionXml === '') {
            return '';
        }

        $xml = '';
        if (preg_match_all('/<(?:air:)?AirPricingInfo\b([^>]*)>([\s\S]*?)<\/(?:air:)?AirPricingInfo>/i', $solutionXml, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attrs = self::normalizeStorePriceInnerXml((string) ($match[1] ?? ''), $travelerKeyMap, $segmentKeyMap);
                $inner = self::normalizeStorePriceInnerXml((string) ($match[2] ?? ''), $travelerKeyMap, $segmentKeyMap);
                $inner = self::injectBookingTravelerRefsIntoStorePriceXml($inner, $bookingTravelerRefs);
                $xml .= "\n                    <air:AirPricingInfo{$attrs}>{$inner}</air:AirPricingInfo>";
            }
        }

        if (preg_match_all('/<(?:[\w-]+:)?HostToken\s+Key="([^"]+)"(?:\s+Host="([^"]*)")?>([^<]+)<\/(?:[\w-]+:)?HostToken>/i', $solutionXml, $tokenMatches, PREG_SET_ORDER)) {
            foreach ($tokenMatches as $match) {
                $hostAttr = trim((string) ($match[2] ?? '')) !== ''
                    ? ' Host="' . htmlspecialchars($match[2], ENT_XML1) . '"'
                    : '';
                $key = htmlspecialchars((string) ($match[1] ?? ''), ENT_XML1);
                $value = htmlspecialchars((string) ($match[3] ?? ''), ENT_XML1);
                $xml .= "\n                    <com:HostToken Key=\"{$key}\"{$hostAttr}>{$value}</com:HostToken>";
            }
        }

        return trim($xml);
    }

    /**
     * airPrice on a held PNR returns fresh segment keys; UR modify must reference held keys.
     *
     * @param  list<array<string, mixed>>  $heldAirPriceSegments
     * @return array<string, string>
     */
    public static function buildQuoteToHeldSegmentKeyMap(string $airPriceRaw, array $heldAirPriceSegments): array
    {
        if ($airPriceRaw === '' || $heldAirPriceSegments === []) {
            return [];
        }

        $heldSegments = [];
        foreach ($heldAirPriceSegments as $held) {
            if (! is_array($held)) {
                continue;
            }

            $heldKey = trim((string) ($held['Key'] ?? ''));
            if ($heldKey === '') {
                continue;
            }

            $heldSegments[] = [
                'key' => $heldKey,
                'carrier' => strtoupper(trim((string) ($held['Carrier'] ?? ''))),
                'flight_number' => trim((string) ($held['FlightNumber'] ?? '')),
                'origin' => strtoupper(trim((string) ($held['Origin'] ?? ''))),
                'destination' => strtoupper(trim((string) ($held['Destination'] ?? ''))),
                'dep_time' => trim((string) ($held['DepartureTime'] ?? '')),
            ];
        }

        if ($heldSegments === []) {
            return [];
        }

        $map = [];
        preg_match_all('/<air:AirSegment\s+([^>]+?)\/?>/i', $airPriceRaw, $segMatches, PREG_SET_ORDER);
        foreach ($segMatches as $match) {
            $attrs = self::parseAttributeString($match[1]);
            $quoteKey = trim((string) ($attrs['Key'] ?? ''));
            if ($quoteKey === '' || isset($map[$quoteKey])) {
                continue;
            }

            $quoteSegment = [
                'carrier' => strtoupper(trim((string) ($attrs['Carrier'] ?? ''))),
                'flight_number' => trim((string) ($attrs['FlightNumber'] ?? '')),
                'origin' => strtoupper(trim((string) ($attrs['Origin'] ?? ''))),
                'destination' => strtoupper(trim((string) ($attrs['Destination'] ?? ''))),
                'dep_time' => trim((string) ($attrs['DepartureTime'] ?? '')),
            ];

            foreach ($heldSegments as $heldSegment) {
                if (! self::segmentsMatchForStorePrice($quoteSegment, $heldSegment)) {
                    continue;
                }

                $heldKey = trim((string) ($heldSegment['key'] ?? ''));
                if ($heldKey !== '' && $heldKey !== $quoteKey) {
                    $map[$quoteKey] = $heldKey;
                }
                break;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $travelerKeyMap
     * @param  array<string, string>  $segmentKeyMap
     */
    private static function normalizeStorePriceInnerXml(
        string $xml,
        array $travelerKeyMap,
        array $segmentKeyMap,
    ): string {
        $xml = str_replace(['common_v52_0:', 'common_v34_0:'], 'com:', $xml);

        if ($segmentKeyMap !== []) {
            foreach ($segmentKeyMap as $from => $to) {
                if ($from === '' || $to === '' || $from === $to) {
                    continue;
                }
                $xml = str_replace('SegmentRef="' . $from . '"', 'SegmentRef="' . $to . '"', $xml);
                $xml = str_replace('AirSegmentRef="' . $from . '"', 'AirSegmentRef="' . $to . '"', $xml);
            }
        }

        return self::remapTravelerRefsInXml($xml, $travelerKeyMap);
    }

    /**
     * @param  list<string>  $bookingTravelerRefs
     */
    private static function injectBookingTravelerRefsIntoStorePriceXml(
        string $inner,
        array $bookingTravelerRefs,
    ): string {
        if ($bookingTravelerRefs === []) {
            return $inner;
        }

        $refIndex = 0;
        $inner = preg_replace_callback(
            '/<(?:air:)?PassengerType\b([^>]*)\/>/i',
            static function (array $match) use (&$refIndex, $bookingTravelerRefs): string {
                $attrs = (string) ($match[1] ?? '');
                if (stripos($attrs, 'BookingTravelerRef=') !== false) {
                    return $match[0];
                }

                $ref = trim((string) ($bookingTravelerRefs[$refIndex] ?? ''));
                $refIndex++;
                if ($ref === '') {
                    return $match[0];
                }

                $refEsc = htmlspecialchars($ref, ENT_XML1);

                return '<air:PassengerType' . $attrs . ' BookingTravelerRef="' . $refEsc . '"/>';
            },
            $inner,
        ) ?? $inner;

        // Store Price pastes airPrice AirPricingInfo into UR Modify. Only set
        // BookingTravelerRef on PassengerType — child BookingTravelerRef elements
        // appended after BaggageAllowances violate the v52 schema element order.

        return $inner;
    }

    /**
     * @param  array<string, string>  $quoteSegment
     * @param  array<string, string>  $heldSegment
     */
    private static function segmentsMatchForStorePrice(array $quoteSegment, array $heldSegment): bool
    {
        foreach (['carrier', 'flight_number', 'origin', 'destination'] as $field) {
            $quoteValue = trim((string) ($quoteSegment[$field] ?? ''));
            $heldValue = trim((string) ($heldSegment[$field] ?? ''));
            if ($quoteValue !== '' && $heldValue !== '' && strtoupper($quoteValue) !== strtoupper($heldValue)) {
                return false;
            }
        }

        $quoteDep = trim((string) ($quoteSegment['dep_time'] ?? ''));
        $heldDep = trim((string) ($heldSegment['dep_time'] ?? ''));
        if ($quoteDep !== '' && $heldDep !== '' && substr($quoteDep, 0, 10) !== substr($heldDep, 0, 10)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, string>  $travelerKeyMap
     */
    private static function remapTravelerRefsInXml(string $xml, array $travelerKeyMap): string
    {
        if ($travelerKeyMap === []) {
            return $xml;
        }

        foreach ($travelerKeyMap as $from => $to) {
            if ($from === '' || $to === '' || $from === $to) {
                continue;
            }
            $xml = str_replace('BookingTravelerRef="' . $from . '"', 'BookingTravelerRef="' . $to . '"', $xml);
        }

        return $xml;
    }

    /**
     * @param  list<array<string, mixed>>  $heldSegments
     * @return list<array<string, mixed>>
     */
    public static function heldSegmentsToAirPriceFormat(array $heldSegments): array
    {
        $segments = [];
        foreach ($heldSegments as $segment) {
            if (! is_array($segment)) {
                continue;
            }
            $key = trim((string) ($segment['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $segments[] = [
                'Key' => $key,
                'Group' => '0',
                'ProviderCode' => '1G',
                'Carrier' => (string) ($segment['carrier'] ?? ''),
                'FlightNumber' => (string) ($segment['flight_number'] ?? ''),
                'Origin' => (string) ($segment['origin'] ?? ''),
                'Destination' => (string) ($segment['destination'] ?? ''),
                'DepartureTime' => (string) ($segment['dep_time'] ?? ''),
                'ArrivalTime' => (string) ($segment['arr_time'] ?? ''),
                'ClassOfService' => (string) ($segment['booking_code'] ?? ''),
                'Equipment' => '320',
            ];
        }

        return $segments;
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     * @return list<array<string, mixed>>
     */
    private static function dedupeSegmentsByKey(array $segments): array
    {
        $unique = [];
        $seenKeys = [];
        foreach ($segments as $segment) {
            if (! is_array($segment)) {
                continue;
            }
            $key = (string) ($segment['key'] ?? '');
            if ($key === '' || isset($seenKeys[$key])) {
                continue;
            }
            $seenKeys[$key] = true;
            $unique[] = $segment;
        }

        return $unique;
    }

    /**
     * @return array<string, string>
     */
    private static function parseAttributeString(string $attrString): array
    {
        $result = [];
        if (preg_match_all('/(\w+)="([^"]*)"/', $attrString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $result[$match[1]] = $match[2];
            }
        }

        return $result;
    }

    private static function firstPricingSolutionXml(string $rawXml): string
    {
        if (preg_match('/<air:AirPricingSolution[\s\S]*?<\/air:AirPricingSolution>/i', $rawXml, $match)) {
            return $match[0];
        }

        return $rawXml;
    }
}
