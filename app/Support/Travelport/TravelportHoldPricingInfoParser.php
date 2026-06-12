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
                ?? $parsed['Body']['UniversalRecordModifyRsp']
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
     * Keys here already come from inside the committed Universal Record (quote
     * blocks scrubbed). A reservation-scoped StoredFare key is valid even when it
     * matches the saved airPrice pricing_info_key — Galileo often retains the same
     * key on the PNR — so only genuine shop-session keys are dropped.
     *
     * @param  list<string>  $keys
     * @return list<string>
     */
    public static function filterQuoteKeys(array $keys, string $airPriceKey): array
    {
        $keys = self::uniqueNonEmpty($keys);

        $reservationKeys = self::filterToReservationKeys($keys);
        if ($reservationKeys !== []) {
            return $reservationKeys;
        }

        $withoutShopQuote = array_values(array_filter(
            $keys,
            static fn (string $key): bool => ! self::isShopSessionKey($key)
                && ($airPriceKey === '' || $key !== $airPriceKey),
        ));

        return $withoutShopQuote !== [] ? $withoutShopQuote : $keys;
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
            'Body.UniversalRecordModifyRsp.UniversalRecord.AirReservation',
            'UniversalRecordRetrieveRsp.UniversalRecord.AirReservation',
            'UniversalRecordModifyRsp.UniversalRecord.AirReservation',
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

        if ($key === '') {
            return [];
        }

        // Inside a committed AirReservation any AirPricingInfo key is usable,
        // even when PricingType is Auto/TicketRecord rather than StoredFare.
        if (! self::isReservationKey($key) && ! self::isPricingKeyShape($key)) {
            return [];
        }

        return [$key];
    }

    private static function isPricingKeyShape(string $key): bool
    {
        $key = trim($key);

        return $key !== ''
            && ! str_starts_with($key, 'traveler_')
            && preg_match('#^[A-Za-z0-9+/=_.\-]{8,}$#', $key) === 1;
    }

    /**
     * Stored fares on the PNR — ignore AirSolutionChangedInfo quote blocks.
     *
     * @return list<string>
     */
    private static function extractStoredFareKeysFromRawXml(string $raw): array
    {
        $universalBlock = self::extractUniversalRecordBlock($raw);
        // When scoped to the committed UR (quote blocks removed) any StoredFare key
        // is genuine — the host may use a key prefix we don't pattern-match (xYM on
        // some PNRs), so trust the structure instead of the prefix.
        $scopedToUr = $universalBlock !== '';
        $searchRaw = $scopedToUr ? $universalBlock : self::scrubQuoteBlocks($raw);
        $keys = [];

        if (preg_match_all(
            '/<(?:[\w-]+:)?AirPricingInfo\b[^>]*\bPricingType=(["\'])StoredFare\1[^>]*\bKey=(["\'])([^"\']+)\2/i',
            $searchRaw,
            $matches,
            PREG_SET_ORDER,
        )) {
            foreach ($matches as $match) {
                $keys[] = (string) ($match[3] ?? '');
            }
        }

        if (preg_match_all(
            '/<(?:[\w-]+:)?AirPricingInfo\b[^>]*\bKey=(["\'])([^"\']+)\1[^>]*\bPricingType=(["\'])StoredFare\3/i',
            $searchRaw,
            $matches,
            PREG_SET_ORDER,
        )) {
            foreach ($matches as $match) {
                $keys[] = (string) ($match[2] ?? '');
            }
        }

        if (preg_match_all(
            '/<(?:[\w-]+:)?AirPricingInfo\b[^>]*\bPricingType="StoredFare"[^>]*\bKey="([^"]+)"/i',
            $searchRaw,
            $matches,
        )) {
            $keys = array_merge($keys, $matches[1] ?? []);
        }

        if (preg_match_all(
            '/<(?:[\w-]+:)?AirPricingInfo\b[^>]*\bKey="([^"]+)"[^>]*\bPricingType="StoredFare"/i',
            $searchRaw,
            $matches,
        )) {
            $keys = array_merge($keys, $matches[1] ?? []);
        }

        $keys = $scopedToUr
            ? self::filterToUsableKeys(self::uniqueNonEmpty($keys))
            : self::filterToReservationKeys(self::uniqueNonEmpty($keys));
        if ($keys !== []) {
            return $keys;
        }

        if (! preg_match_all(
            '/<(?:[\w-]+:)?AirReservation\b[\s\S]*?<\/(?:[\w-]+:)?AirReservation>/i',
            $searchRaw,
            $reservationMatches,
        )) {
            return [];
        }

        foreach ($reservationMatches[0] as $reservationBlock) {
            if (! preg_match_all(
                '/<(?:[\w-]+:)?AirPricingInfo\b[^>]*\bKey=(["\'])([^"\']+)\1/i',
                $reservationBlock,
                $matches,
                PREG_SET_ORDER,
            )) {
                continue;
            }

            foreach ($matches as $match) {
                $keys[] = (string) ($match[2] ?? '');
            }
        }

        return $scopedToUr
            ? self::uniqueNonEmpty(self::filterToUsableKeys($keys))
            : self::uniqueNonEmpty(self::filterToReservationKeys($keys));
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    private static function filterToUsableKeys(array $keys): array
    {
        return array_values(array_filter(
            self::uniqueNonEmpty($keys),
            static fn (string $key): bool => TravelportGdsKeyFormat::isUsableTravelerKey($key),
        ));
    }

    private static function extractUniversalRecordBlock(string $raw): string
    {
        $scrubbed = self::scrubQuoteBlocks($raw);
        if ($scrubbed === '') {
            return '';
        }

        $patterns = [
            '/<(?:[\w-]+:)?AirCreateReservationRsp\b[\s\S]*?<(?:[\w-]+:)?UniversalRecord\b[\s\S]*?<\/(?:[\w-]+:)?UniversalRecord>/i',
            '/<(?:[\w-]+:)?UniversalRecordModifyRsp\b[\s\S]*?<(?:[\w-]+:)?UniversalRecord\b[\s\S]*?<\/(?:[\w-]+:)?UniversalRecord>/i',
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
