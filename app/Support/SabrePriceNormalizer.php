<?php

namespace App\Support;

final class SabrePriceNormalizer
{
    public static function normalize(mixed $value): ?float
    {
        if (is_array($value)) {
            foreach (['amount', 'Amount', 'totalPrice', 'TotalPrice', 'value', 'Value'] as $key) {
                if (array_key_exists($key, $value)) {
                    return self::normalize($value[$key]);
                }
            }

            return null;
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $amount = round((float) $value, 2);

        return $amount > 0 ? $amount : null;
    }
}
