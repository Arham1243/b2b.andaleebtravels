<?php

namespace App\Support\Travelport;

/**
 * After AirCreateReservation, Galileo replaces request keys (traveler_1) with GDS keys (D1/…).
 * Fare storage on an existing universal record must reference the GDS keys.
 */
final class TravelportHoldTravelerKeyResolver
{
    /**
     * @param  array<string, mixed>  $holdResponse
     * @param  list<array<string, mixed>>  $travelers
     * @return array<string, string> request key => GDS key
     */
    public static function resolveRequestToGdsKeyMap(array $holdResponse, array $travelers): array
    {
        if ($travelers === []) {
            return [];
        }

        $gdsTravelers = self::extractBookingTravelersFromHold($holdResponse);
        if ($gdsTravelers === []) {
            return [];
        }

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
            if ($gdsTraveler === null) {
                $gdsTraveler = self::matchByIdentity($gdsTravelers, $traveler);
            }

            $gdsKey = trim((string) ($gdsTraveler['key'] ?? ''));
            if ($gdsKey !== '') {
                $map[$requestKey] = $gdsKey;
            }
        }

        return $map;
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
     * @param  array<string, mixed>  $holdResponse
     * @return list<array{key: string, traveler_type: string, first: string, last: string}>
     */
    public static function extractBookingTravelersFromHold(array $holdResponse): array
    {
        $parsed = is_array($holdResponse['parsed'] ?? null) ? $holdResponse['parsed'] : [];
        $travelers = [];

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
                    $travelers[] = $normalized;
                }
            }
        }

        if ($travelers !== []) {
            return $travelers;
        }

        $raw = (string) ($holdResponse['raw'] ?? '');
        if ($raw === '') {
            return [];
        }

        if (! preg_match(
            '/<(?:[\w-]+:)?UniversalRecord\b[\s\S]*?<\/(?:[\w-]+:)?UniversalRecord>/i',
            $raw,
            $universalMatch,
        )) {
            return [];
        }

        $universalBlock = $universalMatch[0];

        if (! preg_match_all(
            '/<(?:[\w-]+:)?BookingTraveler\b([^>]*)>([\s\S]*?)<\/(?:[\w-]+:)?BookingTraveler>/i',
            $universalBlock,
            $matches,
            PREG_SET_ORDER,
        )) {
            return [];
        }

        foreach ($matches as $match) {
            $attrs = (string) ($match[1] ?? '');
            $body = (string) ($match[2] ?? '');

            $key = self::attributeValue($attrs, 'Key');
            if ($key === '') {
                continue;
            }

            $travelers[] = [
                'key' => $key,
                'traveler_type' => strtoupper(self::attributeValue($attrs, 'TravelerType')),
                'first' => self::normalizeName(self::elementAttributeValue($body, 'BookingTravelerName', 'First')),
                'last' => self::normalizeName(self::elementAttributeValue($body, 'BookingTravelerName', 'Last')),
            ];
        }

        return $travelers;
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
            if ($type !== '' && $type !== ($gdsTraveler['traveler_type'] ?? '')) {
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
        if ($key === '') {
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
        if (preg_match('/\b' . preg_quote($name, '/') . '="([^"]*)"/i', $attributeString, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    private static function elementAttributeValue(string $xml, string $element, string $attribute): string
    {
        if (! preg_match(
            '/<(?:[\w-]+:)?' . preg_quote($element, '/') . '\b[^>]*\b' . preg_quote($attribute, '/') . '="([^"]*)"/i',
            $xml,
            $matches,
        )) {
            return '';
        }

        return trim($matches[1]);
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
