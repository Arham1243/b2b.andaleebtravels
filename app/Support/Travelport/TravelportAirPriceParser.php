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
            $segmentRefs = $refMatches[1] ?? [];
        }

        foreach ($segmentRefs as $refKey) {
            if (isset($segmentsByKey[$refKey])) {
                $seg = $segmentsByKey[$refKey];
                foreach ($pd['booking_infos'] as $bi) {
                    if (($bi['segment_ref'] ?? '') === $refKey && ($bi['booking_code'] ?? '') !== '') {
                        $seg['booking_code'] = $bi['booking_code'];
                        break;
                    }
                }
                $pd['segments'][] = $seg;
            }
        }

        if ($pd['segments'] === []) {
            $pd['segments'] = array_values($segmentsByKey);
        }

        preg_match_all('/<common_v52_0:HostToken\s+Key="([^"]+)">([^<]+)<\/common_v52_0:HostToken>/i', $solutionXml, $htMatches, PREG_SET_ORDER);
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

        return $pd;
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
