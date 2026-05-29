<?php

namespace App\Support;

use App\Models\Config;

class B2bConfig
{
    /** @var array<string, string>|null */
    private static ?array $cache = null;

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = Config::pluck('config_value', 'config_key')->toArray();
        }

        return self::$cache;
    }

    public static function value(string $b2bKey, ?string $legacyKey = null, string $default = ''): string
    {
        $config = self::all();
        $value = trim((string) ($config[$b2bKey] ?? ''));

        if ($value === '' && $legacyKey !== null) {
            $value = trim((string) ($config[$legacyKey] ?? ''));
        }

        return $value !== '' ? $value : $default;
    }
}
