<?php

namespace App\Support\Travelport;

/**
 * Extract AirPricingInfo keys from a Travelport AirCreateReservation (hold) response.
 */
class TravelportHoldPricingInfoParser
{
    /**
     * @param  array<string, mixed>  $holdResponse  Full hold API response or booking_response array
     * @return list<string>
     */
    public static function extractKeys(array $holdResponse): array
    {
        $keys = [];

        $parsed = is_array($holdResponse['parsed'] ?? null) ? $holdResponse['parsed'] : [];
        $bookingBody = $holdResponse;
        if ($parsed !== []) {
            $bookingBody = $parsed['Body']['AirCreateReservationRsp']
                ?? $parsed['Body']['UniversalRecordRetrieveRsp']
                ?? $parsed['UniversalRecordRetrieveRsp']
                ?? $parsed;
        }

        if (is_array($bookingBody)) {
            $keys = array_merge($keys, self::extractKeysFromParsedBody($bookingBody));
            $keys = array_merge($keys, self::extractKeysFromParsedRecursive($bookingBody));
        }

        $raw = (string) ($holdResponse['raw'] ?? $bookingBody['raw'] ?? '');
        if ($raw !== '') {
            $keys = array_merge($keys, self::extractKeysFromRawXml($raw));
        }

        return self::uniqueNonEmpty($keys);
    }

    /**
     * @param  array<string, mixed>  $bookingRequest
     * @param  array<string, mixed>  $bookingResponse
     * @return list<string>
     */
    public static function resolveKeysForTicketing(array $bookingRequest, array $bookingResponse): array
    {
        $airPriceKey = trim((string) data_get($bookingRequest, 'pricing_data.pricing_info_key', ''));

        $fromHold = self::filterQuoteKeys(self::extractKeys($bookingResponse), $airPriceKey);
        if ($fromHold !== []) {
            return $fromHold;
        }

        $persisted = $bookingRequest['hold_air_pricing_info_keys'] ?? [];
        if (is_array($persisted) && $persisted !== []) {
            $fromPersisted = self::filterQuoteKeys(
                self::uniqueNonEmpty(array_map('strval', $persisted)),
                $airPriceKey,
            );
            if ($fromPersisted !== []) {
                return $fromPersisted;
            }
        }

        return [];
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    public static function filterQuoteKeys(array $keys, string $airPriceKey): array
    {
        $keys = self::uniqueNonEmpty($keys);
        if ($keys === [] || $airPriceKey === '') {
            return $keys;
        }

        $filtered = array_values(array_filter(
            $keys,
            static fn (string $key): bool => $key !== $airPriceKey,
        ));

        return $filtered !== [] ? $filtered : [];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return list<string>
     */
    private static function extractKeysFromParsedBody(array $body): array
    {
        $keys = [];
        $paths = [
            'UniversalRecord.AirReservation.AirPricingInfo',
            'Body.AirCreateReservationRsp.UniversalRecord.AirReservation.AirPricingInfo',
            'Body.UniversalRecordRetrieveRsp.UniversalRecord.AirReservation.AirPricingInfo',
            'UniversalRecordRetrieveRsp.UniversalRecord.AirReservation.AirPricingInfo',
            'AirReservation.AirPricingInfo',
        ];

        foreach ($paths as $path) {
            $node = data_get($body, $path);
            if ($node !== null) {
                $keys = array_merge($keys, self::keysFromPricingInfoNode($node));
            }
        }

        return $keys;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return list<string>
     */
    private static function extractKeysFromParsedRecursive(array $node): array
    {
        $keys = [];

        foreach ($node as $key => $value) {
            if (is_string($key) && str_ends_with(strtolower($key), 'airpricinginfo')) {
                $keys = array_merge($keys, self::keysFromPricingInfoNode($value));
            }

            if (is_array($value)) {
                $keys = array_merge($keys, self::extractKeysFromParsedRecursive($value));
            }
        }

        return $keys;
    }

    /**
     * @param  mixed  $node
     * @return list<string>
     */
    private static function keysFromPricingInfoNode(mixed $node): array
    {
        if (! is_array($node)) {
            return [];
        }

        if (self::isListArray($node)) {
            $keys = [];
            foreach ($node as $item) {
                $keys = array_merge($keys, self::keyFromSinglePricingInfo($item));
            }

            return $keys;
        }

        return self::keyFromSinglePricingInfo($node);
    }

    /**
     * @param  mixed  $item
     * @return list<string>
     */
    private static function keyFromSinglePricingInfo(mixed $item): array
    {
        if (! is_array($item)) {
            return [];
        }

        $key = trim((string) (
            $item['@attributes']['Key']
            ?? $item['Key']
            ?? data_get($item, '@attributes.Key')
            ?? ''
        ));

        return $key !== '' ? [$key] : [];
    }

    /**
     * Only keys under AirReservation count for ticketing — not AirSolutionChangedInfo quotes.
     *
     * @return list<string>
     */
    private static function extractKeysFromRawXml(string $raw): array
    {
        if (! preg_match('/<(?:[\w-]+:)?AirReservation\b[\s\S]*?<\/(?:[\w-]+:)?AirReservation>/i', $raw, $reservationMatch)) {
            return [];
        }

        if (! preg_match_all('/<(?:[\w-]+:)?AirPricingInfo\b[^>]*\bKey="([^"]+)"/i', $reservationMatch[0], $matches)) {
            return [];
        }

        return self::uniqueNonEmpty($matches[1] ?? []);
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    private static function uniqueNonEmpty(array $keys): array
    {
        $seen = [];
        $unique = [];

        foreach ($keys as $key) {
            $normalized = trim((string) $key);
            if ($normalized === '' || isset($seen[$normalized])) {
                continue;
            }
            $seen[$normalized] = true;
            $unique[] = $normalized;
        }

        return $unique;
    }

    /**
     * @param  array<mixed>  $array
     */
    private static function isListArray(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }
}
