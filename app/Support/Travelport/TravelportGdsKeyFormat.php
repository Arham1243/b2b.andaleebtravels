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
}
