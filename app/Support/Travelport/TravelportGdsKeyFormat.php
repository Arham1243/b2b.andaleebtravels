<?php

namespace App\Support\Travelport;

/**
 * Distinguish reservation-scoped GDS keys (D1/, /NA, …) from shop-session keys (xYM…).
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

        if (str_starts_with($key, 'D1/') || str_starts_with($key, '/NA')) {
            return true;
        }

        // Reservation objects use a slash namespace (D1/…, /NA…). Shop quote keys do not.
        return str_contains($key, '/');
    }

    public static function isShopSessionKey(string $key): bool
    {
        return preg_match('/^xYM/i', trim($key)) === 1;
    }
}
