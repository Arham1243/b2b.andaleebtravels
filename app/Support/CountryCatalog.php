<?php

namespace App\Support;

use App\Models\Country;
use Illuminate\Support\Facades\Cache;

class CountryCatalog
{
    /** @var list<string> */
    private const PRIORITY_CODES = [
        'AE', 'SA', 'QA', 'OM', 'KW', 'BH', 'PK', 'IN', 'BD', 'LK', 'NP', 'PH',
        'GB', 'US', 'CA', 'AU', 'DE', 'FR', 'IT', 'ES', 'TR', 'EG', 'JO', 'LB',
        'CN', 'JP', 'KR', 'SG', 'MY', 'TH', 'ID', 'ZA', 'KE', 'NG', 'RU', 'UA',
    ];

    /**
     * @return list<array{code: string, name: string}>
     */
    public static function forAutocomplete(): array
    {
        return Cache::remember('country_catalog_autocomplete_v1', 3600, function () {
            $countries = self::fromDatabase();

            if ($countries === []) {
                $countries = self::fromJsonFallback();
            }

            return self::prioritize($countries);
        });
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    private static function fromDatabase(): array
    {
        try {
            return Country::query()
                ->whereNotNull('iso_code')
                ->where('iso_code', '!=', '')
                ->orderBy('name')
                ->get(['name', 'iso_code'])
                ->map(static function (Country $country): ?array {
                    $code = strtoupper(trim((string) $country->iso_code));
                    $name = trim((string) $country->name);

                    if (strlen($code) !== 2 || $name === '') {
                        return null;
                    }

                    return ['code' => $code, 'name' => $name];
                })
                ->filter()
                ->unique('code')
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    private static function fromJsonFallback(): array
    {
        $path = public_path('user/mocks/countries.json');

        if (!is_readable($path)) {
            return [];
        }

        $raw = json_decode((string) file_get_contents($path), true);

        if (!is_array($raw)) {
            return [];
        }

        $countries = [];

        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }

            $code = strtoupper(trim((string) ($row['code'] ?? '')));
            $name = trim((string) ($row['name'] ?? ''));

            if (strlen($code) !== 2 || $name === '') {
                continue;
            }

            $countries[] = ['code' => $code, 'name' => $name];
        }

        usort($countries, static fn (array $a, array $b) => strcasecmp($a['name'], $b['name']));

        return $countries;
    }

    /**
     * @param  list<array{code: string, name: string}>  $countries
     * @return list<array{code: string, name: string}>
     */
    private static function prioritize(array $countries): array
    {
        $byCode = [];

        foreach ($countries as $country) {
            $byCode[$country['code']] = $country;
        }

        $ordered = [];

        foreach (self::PRIORITY_CODES as $code) {
            if (isset($byCode[$code])) {
                $ordered[] = $byCode[$code];
                unset($byCode[$code]);
            }
        }

        $rest = array_values($byCode);
        usort($rest, static fn (array $a, array $b) => strcasecmp($a['name'], $b['name']));

        return array_merge($ordered, $rest);
    }
}
