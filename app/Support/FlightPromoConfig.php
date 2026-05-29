<?php

namespace App\Support;

use App\Models\Config;

class FlightPromoConfig
{
    /** @var array<string, string> */
    public const DEFAULTS = [
        'FLIGHT_PROMO_1_KICKER' => 'Exclusive Deal',
        'FLIGHT_PROMO_1_TITLE' => 'Hotel Bookings',
        'FLIGHT_PROMO_2_KICKER' => 'Fly from Dubai, Effortlessly',
        'FLIGHT_PROMO_2_TITLE' => "Al Maktoum to\nSaudi Arabia",
        'FLIGHT_PROMO_2_CTA' => 'Operates 4 days a week from DWC to RUH',
        'FLIGHT_PROMO_3_TITLE' => 'NDC FARES',
        'FLIGHT_PROMO_3_CTA' => 'Now Available Exclusively on Online',
    ];

    /**
     * @return array<string, string>
     */
    public static function resolved(?array $config = null): array
    {
        $config = $config ?? Config::pluck('config_value', 'config_key')->toArray();
        $resolved = [];

        foreach (self::DEFAULTS as $key => $default) {
            $value = trim((string) ($config[$key] ?? ''));
            $resolved[$key] = $value !== '' ? $value : $default;
        }

        return $resolved;
    }

    public static function formatMultiline(string $text): string
    {
        return nl2br(e($text), false);
    }
}
