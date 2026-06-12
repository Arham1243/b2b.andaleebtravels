<?php

namespace App\Support;

final class PhoneDialCodeCatalog
{
    /** @var list<array{iso: string, dial: string, name: string}> */
    private const ENTRIES = [
        ['iso' => 'AE', 'dial' => '971', 'name' => 'United Arab Emirates'],
        ['iso' => 'SA', 'dial' => '966', 'name' => 'Saudi Arabia'],
        ['iso' => 'QA', 'dial' => '974', 'name' => 'Qatar'],
        ['iso' => 'OM', 'dial' => '968', 'name' => 'Oman'],
        ['iso' => 'KW', 'dial' => '965', 'name' => 'Kuwait'],
        ['iso' => 'BH', 'dial' => '973', 'name' => 'Bahrain'],
        ['iso' => 'PK', 'dial' => '92', 'name' => 'Pakistan'],
        ['iso' => 'IN', 'dial' => '91', 'name' => 'India'],
        ['iso' => 'BD', 'dial' => '880', 'name' => 'Bangladesh'],
        ['iso' => 'LK', 'dial' => '94', 'name' => 'Sri Lanka'],
        ['iso' => 'NP', 'dial' => '977', 'name' => 'Nepal'],
        ['iso' => 'PH', 'dial' => '63', 'name' => 'Philippines'],
        ['iso' => 'GB', 'dial' => '44', 'name' => 'United Kingdom'],
        ['iso' => 'US', 'dial' => '1', 'name' => 'United States'],
        ['iso' => 'CA', 'dial' => '1', 'name' => 'Canada'],
        ['iso' => 'AU', 'dial' => '61', 'name' => 'Australia'],
        ['iso' => 'DE', 'dial' => '49', 'name' => 'Germany'],
        ['iso' => 'FR', 'dial' => '33', 'name' => 'France'],
        ['iso' => 'IT', 'dial' => '39', 'name' => 'Italy'],
        ['iso' => 'ES', 'dial' => '34', 'name' => 'Spain'],
        ['iso' => 'TR', 'dial' => '90', 'name' => 'Turkey'],
        ['iso' => 'EG', 'dial' => '20', 'name' => 'Egypt'],
        ['iso' => 'JO', 'dial' => '962', 'name' => 'Jordan'],
        ['iso' => 'LB', 'dial' => '961', 'name' => 'Lebanon'],
        ['iso' => 'CN', 'dial' => '86', 'name' => 'China'],
        ['iso' => 'JP', 'dial' => '81', 'name' => 'Japan'],
        ['iso' => 'KR', 'dial' => '82', 'name' => 'South Korea'],
        ['iso' => 'SG', 'dial' => '65', 'name' => 'Singapore'],
        ['iso' => 'MY', 'dial' => '60', 'name' => 'Malaysia'],
        ['iso' => 'TH', 'dial' => '66', 'name' => 'Thailand'],
        ['iso' => 'ID', 'dial' => '62', 'name' => 'Indonesia'],
        ['iso' => 'ZA', 'dial' => '27', 'name' => 'South Africa'],
        ['iso' => 'KE', 'dial' => '254', 'name' => 'Kenya'],
        ['iso' => 'NG', 'dial' => '234', 'name' => 'Nigeria'],
        ['iso' => 'RU', 'dial' => '7', 'name' => 'Russia'],
        ['iso' => 'UA', 'dial' => '380', 'name' => 'Ukraine'],
    ];

    /**
     * @return list<array{iso: string, dial: string, name: string}>
     */
    public static function entries(): array
    {
        return self::ENTRIES;
    }

    public static function isoFromDialCode(string $dialCode): string
    {
        $normalized = ltrim(preg_replace('/\D+/', '', $dialCode) ?? '', '0');

        foreach (self::ENTRIES as $entry) {
            if ($entry['dial'] === $normalized) {
                return $entry['iso'];
            }
        }

        return 'AE';
    }

    /**
     * @return list<string>
     */
    public static function dialCodesLongestFirst(): array
    {
        $codes = array_values(array_unique(array_map(
            static fn (array $entry): string => $entry['dial'],
            self::ENTRIES,
        )));

        usort($codes, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return $codes;
    }
}
