<?php

namespace App\Support\Travelport;

/**
 * Extract AirPricingInfo keys from a Travelport AirCreateReservation (hold) response.
 */
class TravelportHoldPricingInfoParser
{
    /**
     * Reservation-scoped stored fare keys suitable for AirTicketing (D1/… on Galileo).
     *
     * @param  array<string, mixed>  $holdResponse
     * @return list<string>
     */
    public static function extractReservationKeys(array $holdResponse): array
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
            $keys = array_merge($keys, self::extractStoredFareKeysFromRawXml($raw));
        }

        return self::filterToReservationKeys(self::uniqueNonEmpty($keys));
    }

    /**
     * @param  array<string, mixed>  $holdResponse
     * @return list<string>
     */
    public static function extractKeys(array $holdResponse): array
    {
        return self::extractReservationKeys($holdResponse);
    }

    /**
     * @param  array<string, mixed>  $bookingResponse
     */
    public static function extractProviderLocatorCode(array $bookingResponse): string
    {
        $fromMeta = trim((string) ($bookingResponse['travelport_provider_locator'] ?? ''));
        if ($fromMeta !== '') {
            return $fromMeta;
        }

        $parsed = is_array($bookingResponse['parsed'] ?? null) ? $bookingResponse['parsed'] : [];
        $candidates = [
            data_get($parsed, 'Body.AirCreateReservationRsp.UniversalRecord.ProviderReservationInfo.@attributes.LocatorCode'),
            data_get($parsed, 'Body.AirCreateReservationRsp.UniversalRecord.ProviderReservationInfo.LocatorCode'),
            data_get($parsed, 'Body.UniversalRecordRetrieveRsp.UniversalRecord.ProviderReservationInfo.@attributes.LocatorCode'),
            data_get($parsed, 'Body.UniversalRecordRetrieveRsp.UniversalRecord.ProviderReservationInfo.LocatorCode'),
            data_get($bookingResponse, 'UniversalRecord.ProviderReservationInfo.@attributes.LocatorCode'),
            data_get($bookingResponse, 'UniversalRecord.ProviderReservationInfo.LocatorCode'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        $raw = (string) ($bookingResponse['raw'] ?? '');
        if ($raw !== '' && preg_match('/<(?:[\w-]+:)?ProviderReservationInfo\b[^>]*\bLocatorCode="([^"]+)"/i', $raw, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $holdResponse
     * @return list<string>
     */
    public static function extractKeysFromRetrieveResponse(array $holdResponse, array $bookingRequest = []): array
    {
        $airPriceKey = trim((string) data_get($bookingRequest, 'pricing_data.pricing_info_key', ''));

        return self::filterQuoteKeys(self::extractReservationKeys($holdResponse), $airPriceKey);
    }

    /**
     * @param  array<string, mixed>  $bookingRequest
     * @param  array<string, mixed>  $bookingResponse
     * @return list<string>
     */
    public static function resolveKeysForTicketing(array $bookingRequest, array $bookingResponse): array
    {
        $airPriceKey = trim((string) data_get($bookingRequest, 'pricing_data.pricing_info_key', ''));

        $fromHold = self::filterQuoteKeys(self::extractReservationKeys($bookingResponse), $airPriceKey);
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
        $reservationKeys = self::filterToReservationKeys($keys);

        if ($airPriceKey === '') {
            return $reservationKeys !== [] ? $reservationKeys : $keys;
        }

        $withoutShopQuote = array_values(array_filter(
            $keys,
            static fn (string $key): bool => $key !== $airPriceKey && ! self::isShopSessionKey($key),
        ));

        $reservationWithoutQuote = self::filterToReservationKeys($withoutShopQuote);
        if ($reservationWithoutQuote !== []) {
            return $reservationWithoutQuote;
        }

        return $withoutShopQuote;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return list<string>
     */
    private static function extractKeysFromParsedBody(array $body): array
    {
        $keys = [];
        $reservationPaths = [
            'UniversalRecord.AirReservation',
            'Body.AirCreateReservationRsp.UniversalRecord.AirReservation',
            'Body.UniversalRecordRetrieveRsp.UniversalRecord.AirReservation',
            'UniversalRecordRetrieveRsp.UniversalRecord.AirReservation',
            'AirReservation',
        ];

        foreach ($reservationPaths as $path) {
            $node = data_get($body, $path);
            if ($node === null) {
                continue;
            }

            foreach (self::asList($node) as $reservation) {
                if (! is_array($reservation)) {
                    continue;
                }

                $keys = array_merge($keys, self::keysFromPricingInfoNode($reservation['AirPricingInfo'] ?? null));
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
            if (is_string($key) && self::isQuoteContextKey($key)) {
                continue;
            }

            if (is_string($key) && str_ends_with(strtolower(self::stripXmlPrefix($key)), 'airpricinginfo')) {
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

        if ($key === '' || ! self::isReservationKey($key)) {
            return [];
        }

        return [$key];
    }

    /**
     * Stored fares on the PNR — ignore AirSolutionChangedInfo quote blocks.
     *
     * @return list<string>
     */
    private static function extractStoredFareKeysFromRawXml(string $raw): array
    {
        $scrubbed = self::scrubQuoteBlocks($raw);
        $keys = [];

        if (preg_match_all(
            '/<(?:[\w-]+:)?AirPricingInfo\b[^>]*\bPricingType="StoredFare"[^>]*\bKey="([^"]+)"/i',
            $scrubbed,
            $matches,
        )) {
            $keys = array_merge($keys, $matches[1] ?? []);
        }

        if (preg_match_all(
            '/<(?:[\w-]+:)?AirPricingInfo\b[^>]*\bKey="([^"]+)"[^>]*\bPricingType="StoredFare"/i',
            $scrubbed,
            $matches,
        )) {
            $keys = array_merge($keys, $matches[1] ?? []);
        }

        $keys = self::filterToReservationKeys(self::uniqueNonEmpty($keys));
        if ($keys !== []) {
            return $keys;
        }

        if (! preg_match_all(
            '/<(?:[\w-]+:)?AirReservation\b[\s\S]*?<\/(?:[\w-]+:)?AirReservation>/i',
            $scrubbed,
            $reservationMatches,
        )) {
            return [];
        }

        foreach ($reservationMatches[0] as $reservationBlock) {
            if (! preg_match_all(
                '/<(?:[\w-]+:)?AirPricingInfo\b[^>]*\bKey="([^"]+)"/i',
                $reservationBlock,
                $matches,
            )) {
                continue;
            }

            $keys = array_merge($keys, self::filterToReservationKeys($matches[1] ?? []));
        }

        return self::uniqueNonEmpty($keys);
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
     * @param  list<string>  $keys
     * @return list<string>
     */
    private static function filterToReservationKeys(array $keys): array
    {
        return array_values(array_filter(
            self::uniqueNonEmpty($keys),
            static fn (string $key): bool => self::isReservationKey($key),
        ));
    }

    private static function isReservationKey(string $key): bool
    {
        return TravelportGdsKeyFormat::isReservationScopedKey($key);
    }

    private static function isShopSessionKey(string $key): bool
    {
        return TravelportGdsKeyFormat::isShopSessionKey($key);
    }

    private static function isQuoteContextKey(string $key): bool
    {
        $normalized = strtolower(self::stripXmlPrefix($key));

        return in_array($normalized, ['airsolutionchangedinfo', 'airpricingsolution'], true);
    }

    private static function stripXmlPrefix(string $key): string
    {
        return (string) preg_replace('/^[\w-]+:/', '', $key);
    }

    /**
     * @param  mixed  $node
     * @return list<mixed>
     */
    private static function asList(mixed $node): array
    {
        if (! is_array($node)) {
            return [];
        }

        return self::isListArray($node) ? $node : [$node];
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
