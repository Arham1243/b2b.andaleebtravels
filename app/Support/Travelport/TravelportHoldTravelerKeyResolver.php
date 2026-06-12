<?php

namespace App\Support\Travelport;

/**
 * After AirCreateReservation, Galileo replaces request keys (traveler_1) with GDS keys (D1/, /NA, …).
 * Fare storage on an existing universal record must reference the GDS keys.
 */
final class TravelportHoldTravelerKeyResolver
{
    /**
     * @param  array<string, mixed>  $holdResponse
     * @param  list<array<string, mixed>>  $travelers
     * @return array<string, string> request key => GDS key
     */
    /**
     * @param  array<string, mixed>  ...$holdResponses
     * @return array<string, string>
     */
    public static function resolveRequestToGdsKeyMapFromSources(array $travelers, array ...$holdResponses): array
    {
        if ($travelers === []) {
            return [];
        }

        $gdsTravelers = [];
        foreach ($holdResponses as $holdResponse) {
            if (! is_array($holdResponse) || $holdResponse === []) {
                continue;
            }

            $gdsTravelers = self::mergeTravelersByKey(
                $gdsTravelers,
                self::extractBookingTravelersFromHold($holdResponse),
            );
        }

        if ($gdsTravelers === []) {
            return [];
        }

        return self::buildRequestToGdsKeyMap($travelers, $gdsTravelers);
    }

    public static function resolveRequestToGdsKeyMap(array $holdResponse, array $travelers): array
    {
        return self::resolveRequestToGdsKeyMapFromSources($travelers, $holdResponse);
    }

    /**
     * @param  list<array<string, mixed>>  $travelers
     * @param  list<array{key: string, traveler_type: string, first: string, last: string}>  $gdsTravelers
     * @return array<string, string>
     */
    private static function buildRequestToGdsKeyMap(array $travelers, array $gdsTravelers): array
    {
        $map = [];

        foreach ($travelers as $index => $traveler) {
            if (! is_array($traveler)) {
                continue;
            }

            $requestKey = trim((string) ($traveler['key'] ?? ''));
            if ($requestKey === '') {
                continue;
            }

            $gdsTraveler = $gdsTravelers[$index] ?? null;
            if ($gdsTraveler === null || ! TravelportGdsKeyFormat::isUsableTravelerKey((string) ($gdsTraveler['key'] ?? ''))) {
                $gdsTraveler = self::matchByIdentity($gdsTravelers, $traveler);
            }

            $gdsKey = trim((string) ($gdsTraveler['key'] ?? ''));
            if ($gdsKey !== '' && TravelportGdsKeyFormat::isUsableTravelerKey($gdsKey)) {
                $map[$requestKey] = $gdsKey;
            }
        }

        return $map;
    }

    /**
     * @param  list<array{key: string, traveler_type: string, first: string, last: string}>  $existing
     * @param  list<array{key: string, traveler_type: string, first: string, last: string}>  $incoming
     * @return list<array{key: string, traveler_type: string, first: string, last: string}>
     */
    private static function mergeTravelersByKey(array $existing, array $incoming): array
    {
        if ($incoming === []) {
            return $existing;
        }

        if ($existing === []) {
            return $incoming;
        }

        $incomingByKey = [];
        foreach ($incoming as $traveler) {
            $key = trim((string) ($traveler['key'] ?? ''));
            if ($key !== '') {
                $incomingByKey[$key] = $traveler;
            }
        }

        $merged = [];
        $seenKeys = [];

        foreach ($existing as $traveler) {
            $key = trim((string) ($traveler['key'] ?? ''));
            if ($key === '') {
                continue;
            }

            $seenKeys[$key] = true;
            if (isset($incomingByKey[$key])) {
                $incomingTraveler = $incomingByKey[$key];
                $merged[] = [
                    'key' => $key,
                    'traveler_type' => ($traveler['traveler_type'] ?? '') !== ''
                        ? (string) $traveler['traveler_type']
                        : (string) ($incomingTraveler['traveler_type'] ?? ''),
                    'first' => ($traveler['first'] ?? '') !== ''
                        ? (string) $traveler['first']
                        : (string) ($incomingTraveler['first'] ?? ''),
                    'last' => ($traveler['last'] ?? '') !== ''
                        ? (string) $traveler['last']
                        : (string) ($incomingTraveler['last'] ?? ''),
                ];
            } else {
                $merged[] = $traveler;
            }
        }

        foreach ($incoming as $traveler) {
            $key = trim((string) ($traveler['key'] ?? ''));
            if ($key === '' || isset($seenKeys[$key])) {
                continue;
            }

            $seenKeys[$key] = true;
            $merged[] = $traveler;
        }

        return $merged;
    }

    /**
     * Diagnostic: every BookingTraveler Key attribute found in the raw XML, unfiltered.
     *
     * @param  array<string, mixed>  $holdResponse
     * @return list<string>
     */
    public static function sampleExtractedTravelerKeys(array $holdResponse): array
    {
        $keys = [];
        foreach (self::extractBookingTravelersFromHold($holdResponse) as $traveler) {
            $key = trim((string) ($traveler['key'] ?? ''));
            if ($key !== '') {
                $keys[] = $key;
            }
        }

        if ($keys !== []) {
            return $keys;
        }

        $universalBlock = self::extractUniversalRecordBlock((string) ($holdResponse['raw'] ?? ''));
        if ($universalBlock !== '' && preg_match_all(
            '/<(?:[\w-]+:)?BookingTraveler\b[^>]*\bKey=(["\'])([^"\']+)\1/i',
            $universalBlock,
            $matches,
            PREG_SET_ORDER,
        )) {
            foreach ($matches as $match) {
                $key = trim((string) ($match[2] ?? ''));
                if ($key !== '') {
                    $keys[] = 'ur:' . $key;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param  array<string, mixed>  $pricingData
     * @param  array<string, string>  $keyMap
     * @return array<string, mixed>
     */
    public static function remapPricingDataTravelerRefs(array $pricingData, array $keyMap): array
    {
        if ($keyMap === []) {
            return $pricingData;
        }

        $passengerTypes = $pricingData['passenger_types'] ?? [];
        if (! is_array($passengerTypes)) {
            return $pricingData;
        }

        foreach ($passengerTypes as $index => $passengerType) {
            if (! is_array($passengerType)) {
                continue;
            }

            $ref = trim((string) ($passengerType['traveler_ref'] ?? ''));
            if ($ref !== '' && isset($keyMap[$ref])) {
                $passengerTypes[$index]['traveler_ref'] = $keyMap[$ref];
            }
        }

        $pricingData['passenger_types'] = $passengerTypes;

        return $pricingData;
    }

    /**
     * @param  array<string, mixed>  $pricingData
     * @param  array<string, mixed>  $holdResponse
     * @return array<string, mixed>
     */
    public static function remapPricingDataSegmentRefsFromHold(array $pricingData, array $holdResponse): array
    {
        $heldSegments = self::extractHeldAirSegments($holdResponse);
        if ($heldSegments === []) {
            return $pricingData;
        }

        $segmentKeyMap = [];
        foreach ($pricingData['segments'] ?? [] as $index => $segment) {
            if (! is_array($segment)) {
                continue;
            }

            $quoteKey = trim((string) ($segment['key'] ?? ''));
            if ($quoteKey === '') {
                continue;
            }

            $held = self::matchHeldSegment($heldSegments, $segment);
            if ($held === null) {
                continue;
            }

            $heldKey = trim((string) ($held['key'] ?? ''));
            if ($heldKey === '' || $heldKey === $quoteKey) {
                continue;
            }

            $segmentKeyMap[$quoteKey] = $heldKey;
            $pricingData['segments'][$index]['key'] = $heldKey;
        }

        if ($segmentKeyMap === []) {
            return $pricingData;
        }

        foreach ($pricingData['booking_infos'] ?? [] as $index => $bookingInfo) {
            if (! is_array($bookingInfo)) {
                continue;
            }

            $segmentRef = trim((string) ($bookingInfo['segment_ref'] ?? ''));
            if ($segmentRef !== '' && isset($segmentKeyMap[$segmentRef])) {
                $pricingData['booking_infos'][$index]['segment_ref'] = $segmentKeyMap[$segmentRef];
            }
        }

        return $pricingData;
    }

    /**
     * @param  list<array<string, mixed>>  $travelers
     * @param  array<string, string>  $keyMap
     * @return list<array<string, mixed>>
     */
    public static function remapTravelerKeys(array $travelers, array $keyMap): array
    {
        if ($keyMap === []) {
            return $travelers;
        }

        foreach ($travelers as $index => $traveler) {
            if (! is_array($traveler)) {
                continue;
            }

            $requestKey = trim((string) ($traveler['key'] ?? ''));
            if ($requestKey !== '' && isset($keyMap[$requestKey])) {
                $travelers[$index]['key'] = $keyMap[$requestKey];
            }
        }

        return $travelers;
    }

    /**
     * @param  array<string, mixed>  $holdResponse
     * @return list<array{key: string, traveler_type: string, first: string, last: string}>
     */
    public static function extractBookingTravelersFromHold(array $holdResponse): array
    {
        $parsed = is_array($holdResponse['parsed'] ?? null) ? $holdResponse['parsed'] : [];
        if ($parsed !== []) {
            $nodes = data_get($parsed, 'Body.AirCreateReservationRsp.UniversalRecord.BookingTraveler')
                ?? data_get($parsed, 'Body.UniversalRecordRetrieveRsp.UniversalRecord.BookingTraveler')
                ?? data_get($parsed, 'UniversalRecord.BookingTraveler');

            $travelers = [];
            foreach (self::asList($nodes) as $node) {
                if (! is_array($node)) {
                    continue;
                }

                $normalized = self::normalizeTravelerNode($node);
                if ($normalized !== null) {
                    $travelers[] = $normalized;
                }
            }

            if ($travelers !== []) {
                return $travelers;
            }
        }

        $universalBlock = self::extractUniversalRecordBlock((string) ($holdResponse['raw'] ?? ''));
        if ($universalBlock !== '') {
            $travelers = self::extractAllBookingTravelersFromRaw($universalBlock);
            if ($travelers !== []) {
                return $travelers;
            }

            $travelers = self::extractTravelerRefsFromReservationRaw($universalBlock);
            if ($travelers !== []) {
                return self::enrichTravelersFromParsed($travelers, $holdResponse);
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $holdResponse
     * @return list<array<string, mixed>>
     */
    public static function extractHeldAirSegments(array $holdResponse): array
    {
        $raw = self::scrubQuoteBlocks((string) ($holdResponse['raw'] ?? ''));
        $segments = [];

        if ($raw !== '' && preg_match_all(
            '/<(?:[\w-]+:)?AirReservation\b[\s\S]*?<\/(?:[\w-]+:)?AirReservation>/i',
            $raw,
            $reservationMatches,
        )) {
            foreach ($reservationMatches[0] as $reservationBlock) {
                if (! preg_match_all(
                    '/<(?:[\w-]+:)?AirSegment\b([^>]*)\/?>/i',
                    $reservationBlock,
                    $segmentMatches,
                    PREG_SET_ORDER,
                )) {
                    continue;
                }

                foreach ($segmentMatches as $match) {
                    $attrs = (string) ($match[1] ?? '');
                    $key = self::attributeValue($attrs, 'Key');
                    if ($key === '') {
                        continue;
                    }

                    $segments[] = [
                        'key' => $key,
                        'carrier' => strtoupper(self::attributeValue($attrs, 'Carrier')),
                        'flight_number' => self::attributeValue($attrs, 'FlightNumber'),
                        'origin' => strtoupper(self::attributeValue($attrs, 'Origin')),
                        'destination' => strtoupper(self::attributeValue($attrs, 'Destination')),
                        'dep_time' => self::attributeValue($attrs, 'DepartureTime'),
                        'booking_code' => strtoupper(self::attributeValue($attrs, 'ClassOfService')),
                    ];
                }
            }
        }

        if ($segments !== []) {
            return $segments;
        }

        $parsed = is_array($holdResponse['parsed'] ?? null) ? $holdResponse['parsed'] : [];
        if ($parsed === []) {
            return [];
        }

        $nodes = data_get($parsed, 'Body.AirCreateReservationRsp.UniversalRecord.AirReservation.AirSegment')
            ?? data_get($parsed, 'Body.UniversalRecordRetrieveRsp.UniversalRecord.AirReservation.AirSegment')
            ?? data_get($parsed, 'UniversalRecord.AirReservation.AirSegment');

        foreach (self::asList($nodes) as $node) {
            if (! is_array($node)) {
                continue;
            }

            $attrs = $node['@attributes'] ?? $node;
            $key = trim((string) ($attrs['Key'] ?? $node['Key'] ?? ''));
            if ($key === '') {
                continue;
            }

            $segments[] = [
                'key' => $key,
                'carrier' => strtoupper(trim((string) ($attrs['Carrier'] ?? ''))),
                'flight_number' => trim((string) ($attrs['FlightNumber'] ?? '')),
                'origin' => strtoupper(trim((string) ($attrs['Origin'] ?? ''))),
                'destination' => strtoupper(trim((string) ($attrs['Destination'] ?? ''))),
                'dep_time' => trim((string) ($attrs['DepartureTime'] ?? '')),
                'booking_code' => strtoupper(trim((string) ($attrs['ClassOfService'] ?? ''))),
            ];
        }

        return $segments;
    }

    /**
     * @param  list<array{key: string, traveler_type: string, first: string, last: string}>  $travelers
     * @return list<array{key: string, traveler_type: string, first: string, last: string}>
     */
    private static function filterReservationTravelers(array $travelers): array
    {
        return array_values(array_filter(
            $travelers,
            static fn (array $traveler): bool => TravelportGdsKeyFormat::isUsableTravelerKey((string) ($traveler['key'] ?? '')),
        ));
    }

    /**
     * @param  list<array{key: string, traveler_type: string, first: string, last: string}>  $travelers
     * @param  array<string, mixed>  $holdResponse
     * @return list<array{key: string, traveler_type: string, first: string, last: string}>
     */
    private static function enrichTravelersFromParsed(array $travelers, array $holdResponse): array
    {
        $parsedTravelers = [];
        $parsed = is_array($holdResponse['parsed'] ?? null) ? $holdResponse['parsed'] : [];
        if ($parsed !== []) {
            $nodes = data_get($parsed, 'Body.AirCreateReservationRsp.UniversalRecord.BookingTraveler')
                ?? data_get($parsed, 'Body.UniversalRecordRetrieveRsp.UniversalRecord.BookingTraveler')
                ?? data_get($parsed, 'UniversalRecord.BookingTraveler');

            foreach (self::asList($nodes) as $node) {
                if (! is_array($node)) {
                    continue;
                }

                $normalized = self::normalizeTravelerNode($node);
                if ($normalized !== null) {
                    $parsedTravelers[$normalized['key']] = $normalized;
                }
            }
        }

        foreach ($travelers as $index => $traveler) {
            $key = (string) ($traveler['key'] ?? '');
            if ($key !== '' && isset($parsedTravelers[$key])) {
                $travelers[$index] = array_merge($traveler, array_filter([
                    'traveler_type' => $parsedTravelers[$key]['traveler_type'] ?? '',
                    'first' => $parsedTravelers[$key]['first'] ?? '',
                    'last' => $parsedTravelers[$key]['last'] ?? '',
                ], static fn (string $value): bool => $value !== ''));
            }
        }

        return $travelers;
    }

    /**
     * @return list<array{key: string, traveler_type: string, first: string, last: string}>
     */
    private static function extractAllBookingTravelersFromRaw(string $raw): array
    {
        return self::extractBookingTravelerElementsFromRaw($raw);
    }

    /**
     * @return list<array{key: string, traveler_type: string, first: string, last: string}>
     */
    private static function extractBookingTravelerElementsFromRaw(string $raw): array
    {
        $travelers = [];
        $seenKeys = [];

        if (preg_match_all(
            '/<(?:[\w-]+:)?BookingTraveler\b([^>]*)\/>/i',
            $raw,
            $selfClosingMatches,
            PREG_SET_ORDER,
        )) {
            foreach ($selfClosingMatches as $match) {
                $traveler = self::travelerFromBookingTravelerAttributes((string) ($match[1] ?? ''), '');
                if ($traveler === null) {
                    continue;
                }

                $key = (string) ($traveler['key'] ?? '');
                if ($key === '' || isset($seenKeys[$key])) {
                    continue;
                }

                $seenKeys[$key] = true;
                $travelers[] = $traveler;
            }
        }

        if (preg_match_all(
            '/<(?:[\w-]+:)?BookingTraveler\b([^>]*)>([\s\S]*?)<\/(?:[\w-]+:)?BookingTraveler>/i',
            $raw,
            $matches,
            PREG_SET_ORDER,
        )) {
            foreach ($matches as $match) {
                $traveler = self::travelerFromBookingTravelerAttributes(
                    (string) ($match[1] ?? ''),
                    (string) ($match[2] ?? ''),
                );
                if ($traveler === null) {
                    continue;
                }

                $key = (string) ($traveler['key'] ?? '');
                if ($key === '' || isset($seenKeys[$key])) {
                    continue;
                }

                $seenKeys[$key] = true;
                $travelers[] = $traveler;
            }
        }

        return self::filterReservationTravelers($travelers);
    }

    /**
     * @return array{key: string, traveler_type: string, first: string, last: string}|null
     */
    private static function travelerFromBookingTravelerAttributes(string $attrs, string $body): ?array
    {
        $key = self::attributeValue($attrs, 'Key');
        if ($key === '' || ! TravelportGdsKeyFormat::isUsableTravelerKey($key)) {
            return null;
        }

        return [
            'key' => $key,
            'traveler_type' => strtoupper(self::attributeValue($attrs, 'TravelerType')),
            'first' => self::normalizeName(self::elementAttributeValue($body, 'BookingTravelerName', 'First')),
            'last' => self::normalizeName(self::elementAttributeValue($body, 'BookingTravelerName', 'Last')),
        ];
    }

    /**
     * @return list<array{key: string, traveler_type: string, first: string, last: string}>
     */
    private static function extractUniversalRecordBlock(string $raw): string
    {
        $scrubbed = self::scrubQuoteBlocks($raw);
        if ($scrubbed === '') {
            return '';
        }

        $patterns = [
            '/<(?:[\w-]+:)?AirCreateReservationRsp\b[\s\S]*?<(?:[\w-]+:)?UniversalRecord\b[\s\S]*?<\/(?:[\w-]+:)?UniversalRecord>/i',
            '/<(?:[\w-]+:)?UniversalRecordRetrieveRsp\b[\s\S]*?<(?:[\w-]+:)?UniversalRecord\b[\s\S]*?<\/(?:[\w-]+:)?UniversalRecord>/i',
            '/<(?:[\w-]+:)?UniversalRecord\b[\s\S]*?<\/(?:[\w-]+:)?UniversalRecord>/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $scrubbed, $match)) {
                return (string) ($match[0] ?? '');
            }
        }

        return '';
    }

    private static function extractTravelersFromUniversalRecordRaw(string $raw): array
    {
        return self::extractBookingTravelerElementsFromRaw($raw);
    }

    /**
     * @return list<array{key: string, traveler_type: string, first: string, last: string}>
     */
    private static function extractTravelerRefsFromReservationRaw(string $raw): array
    {
        $travelers = [];
        $seenKeys = [];
        $confirmedTravelerKeys = self::collectRawBookingTravelerKeys($raw);

        if (! preg_match_all(
            '/<(?:[\w-]+:)?AirReservation\b[\s\S]*?<\/(?:[\w-]+:)?AirReservation>/i',
            $raw,
            $reservationMatches,
        )) {
            return [];
        }

        foreach ($reservationMatches[0] as $reservationBlock) {
            if (preg_match_all(
                '/<(?:[\w-]+:)?BookingTravelerRef\b[^>]*\bKey=(["\'])([^"\']+)\1/i',
                $reservationBlock,
                $refMatches,
                PREG_SET_ORDER,
            )) {
                foreach ($refMatches as $refMatch) {
                    $key = trim((string) ($refMatch[2] ?? ''));
                    if ($key === '' || isset($seenKeys[$key]) || ! self::isConfirmedTravelerKey($key, $confirmedTravelerKeys)) {
                        continue;
                    }

                    $seenKeys[$key] = true;
                    $travelers[] = [
                        'key' => $key,
                        'traveler_type' => '',
                        'first' => '',
                        'last' => '',
                    ];
                }
            }

            if (preg_match_all(
                '/<(?:[\w-]+:)?PassengerType\b[^>]*\bBookingTravelerRef=(["\'])([^"\']+)\1/i',
                $reservationBlock,
                $passengerMatches,
                PREG_SET_ORDER,
            )) {
                foreach ($passengerMatches as $passengerMatch) {
                    $key = trim((string) ($passengerMatch[2] ?? ''));
                    if ($key === '' || isset($seenKeys[$key]) || ! self::isConfirmedTravelerKey($key, $confirmedTravelerKeys)) {
                        continue;
                    }

                    $seenKeys[$key] = true;
                    $travelers[] = [
                        'key' => $key,
                        'traveler_type' => '',
                        'first' => '',
                        'last' => '',
                    ];
                }
            }
        }

        return $travelers;
    }

    /**
     * @return array<string, true>
     */
    private static function collectRawBookingTravelerKeys(string $raw): array
    {
        $keys = [];

        if (! preg_match_all(
            '/<(?:[\w-]+:)?BookingTraveler\b[^>]*\bKey=(["\'])([^"\']+)\1/i',
            $raw,
            $matches,
            PREG_SET_ORDER,
        )) {
            return [];
        }

        foreach ($matches as $match) {
            $key = trim((string) ($match[2] ?? ''));
            if (! TravelportGdsKeyFormat::isUsableTravelerKey($key)) {
                continue;
            }

            $keys[$key] = true;
        }

        return $keys;
    }

    /**
     * @param  array<string, true>  $confirmedTravelerKeys
     */
    private static function isConfirmedTravelerKey(string $key, array $confirmedTravelerKeys): bool
    {
        if (! TravelportGdsKeyFormat::isUsableTravelerKey($key)) {
            return false;
        }

        if ($confirmedTravelerKeys === []) {
            return true;
        }

        return isset($confirmedTravelerKeys[$key]);
    }

    /**
     * @param  list<array<string, mixed>>  $heldSegments
     * @param  array<string, mixed>  $quoteSegment
     * @return array<string, mixed>|null
     */
    private static function matchHeldSegment(array $heldSegments, array $quoteSegment): ?array
    {
        $carrier = strtoupper(trim((string) ($quoteSegment['carrier'] ?? '')));
        $flightNumber = trim((string) ($quoteSegment['flight_number'] ?? ''));
        $origin = strtoupper(trim((string) ($quoteSegment['origin'] ?? '')));
        $destination = strtoupper(trim((string) ($quoteSegment['destination'] ?? '')));
        $depTime = self::normalizeSegmentTime((string) ($quoteSegment['dep_time'] ?? ''));

        foreach ($heldSegments as $heldSegment) {
            if (! is_array($heldSegment)) {
                continue;
            }

            if ($carrier !== '' && $carrier !== strtoupper(trim((string) ($heldSegment['carrier'] ?? '')))) {
                continue;
            }

            if ($flightNumber !== '' && $flightNumber !== trim((string) ($heldSegment['flight_number'] ?? ''))) {
                continue;
            }

            if ($origin !== '' && $origin !== strtoupper(trim((string) ($heldSegment['origin'] ?? '')))) {
                continue;
            }

            if ($destination !== '' && $destination !== strtoupper(trim((string) ($heldSegment['destination'] ?? '')))) {
                continue;
            }

            $heldDepTime = self::normalizeSegmentTime((string) ($heldSegment['dep_time'] ?? ''));
            if ($depTime !== '' && $heldDepTime !== '' && $depTime !== $heldDepTime) {
                continue;
            }

            return $heldSegment;
        }

        return null;
    }

    private static function normalizeSegmentTime(string $time): string
    {
        $time = trim($time);
        if ($time === '') {
            return '';
        }

        return (string) preg_replace('/\.\d{3}(\+|-)/', '$1', $time);
    }

    private static function scrubQuoteBlocks(string $raw): string
    {
        $scrubbed = preg_replace(
            '/<(?:[\w-]+:)?AirSolutionChangedInfo\b[\s\S]*?<\/(?:[\w-]+:)?AirSolutionChangedInfo>/i',
            '',
            $raw,
        );

        return is_string($scrubbed) ? $scrubbed : $raw;
    }

    /**
     * @param  list<array{key: string, traveler_type: string, first: string, last: string}>  $gdsTravelers
     * @param  array<string, mixed>  $traveler
     * @return array{key: string, traveler_type: string, first: string, last: string}|null
     */
    private static function matchByIdentity(array $gdsTravelers, array $traveler): ?array
    {
        $type = strtoupper(trim((string) ($traveler['traveler_type'] ?? $traveler['traveler_type_code'] ?? 'ADT')));
        $first = self::normalizeName((string) ($traveler['firstName'] ?? $traveler['first_name'] ?? ''));
        $last = self::normalizeName((string) ($traveler['lastName'] ?? $traveler['last_name'] ?? ''));

        foreach ($gdsTravelers as $gdsTraveler) {
            if ($type !== '' && ($gdsTraveler['traveler_type'] ?? '') !== '' && $type !== ($gdsTraveler['traveler_type'] ?? '')) {
                continue;
            }

            if ($first !== '' && $first !== ($gdsTraveler['first'] ?? '')) {
                continue;
            }

            if ($last !== '' && $last !== ($gdsTraveler['last'] ?? '')) {
                continue;
            }

            return $gdsTraveler;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array{key: string, traveler_type: string, first: string, last: string}|null
     */
    private static function normalizeTravelerNode(array $node): ?array
    {
        $attrs = $node['@attributes'] ?? $node;
        $key = trim((string) ($attrs['Key'] ?? $node['Key'] ?? ''));
        if ($key === '' || ! TravelportGdsKeyFormat::isUsableTravelerKey($key)) {
            return null;
        }

        $nameNode = $node['BookingTravelerName'] ?? null;
        $nameAttrs = is_array($nameNode) ? ($nameNode['@attributes'] ?? $nameNode) : [];

        return [
            'key' => $key,
            'traveler_type' => strtoupper(trim((string) ($attrs['TravelerType'] ?? ''))),
            'first' => self::normalizeName((string) ($nameAttrs['First'] ?? $nameNode['First'] ?? '')),
            'last' => self::normalizeName((string) ($nameAttrs['Last'] ?? $nameNode['Last'] ?? '')),
        ];
    }

    private static function normalizeName(string $name): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($name)) ?? '');
    }

    private static function attributeValue(string $attributeString, string $name): string
    {
        if (preg_match('/\b' . preg_quote($name, '/') . '=(["\'])([^"\']*)\1/i', $attributeString, $matches)) {
            return trim($matches[2]);
        }

        return '';
    }

    private static function elementAttributeValue(string $xml, string $element, string $attribute): string
    {
        if (! preg_match(
            '/<(?:[\w-]+:)?' . preg_quote($element, '/') . '\b[^>]*\b' . preg_quote($attribute, '/') . '=(["\'])([^"\']*)\1/i',
            $xml,
            $matches,
        )) {
            return '';
        }

        return trim($matches[2]);
    }

    /**
     * @return list<mixed>
     */
    private static function asList(mixed $node): array
    {
        if (! is_array($node)) {
            return [];
        }

        if ($node === [] || array_keys($node) === range(0, count($node) - 1)) {
            return $node;
        }

        return [$node];
    }
}
