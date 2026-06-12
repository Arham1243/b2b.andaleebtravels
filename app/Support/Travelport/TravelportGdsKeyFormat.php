<?php

namespace App\Support\Travelport;

/**
 * Distinguish reservation-scoped GDS keys from request keys (traveler_1) and shop-session keys (xYM…).
 *
 * Key shape varies by environment (sandbox D1/…, production /NA… or plain base64), so callers must
 * rely on XML structure (BookingTraveler elements, StoredFare pricing inside AirReservation) and only
 * use this as a sanity filter.
 */
final class TravelportGdsKeyFormat
{
    public static function isReservationScopedKey(string $key): bool
    {
        $key = trim($key);
        if ($key === '') {
            return false;
        }

        if (str_starts_with($key, 'traveler_')) {
            return false;
        }

        if (self::isShopSessionKey($key)) {
            return false;
        }

        return preg_match('#^[A-Za-z0-9+/=_.\-]{12,}$#', $key) === 1;
    }

    public static function isShopSessionKey(string $key): bool
    {
        return preg_match('/^xYM/i', trim($key)) === 1;
    }

    /**
     * A usable booking-traveler key: any non-empty key that is not a request
     * placeholder (traveler_1). Used when the key was already confirmed to come
     * from a real <BookingTraveler> element inside the committed Universal Record,
     * so the prefix (D1/, /NA, xYM-on-PNR, plain base64) no longer matters.
     */
    public static function isUsableTravelerKey(string $key): bool
    {
        $key = trim($key);

        return $key !== '' && ! str_starts_with($key, 'traveler_');
    }
}
