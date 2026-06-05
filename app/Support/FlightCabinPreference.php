<?php

namespace App\Support;

final class FlightCabinPreference
{
    /** @return list<string> */
    public static function uiOptions(): array
    {
        return ['Economy', 'Premium Economy', 'Business', 'First'];
    }

    public static function normalizeUiLabel(?string $label): string
    {
        $label = trim((string) ($label ?? ''));

        if ($label === '' || ! in_array($label, self::uiOptions(), true)) {
            return 'Economy';
        }

        return $label;
    }

    public static function toSabreCode(string $uiLabel): string
    {
        return match (self::normalizeUiLabel($uiLabel)) {
            'Premium Economy' => 'S',
            'Business' => 'C',
            'First' => 'F',
            default => 'Y',
        };
    }

    public static function familyFromSabreCode(?string $code): ?string
    {
        $code = strtoupper(trim((string) ($code ?? '')));

        if ($code === '') {
            return null;
        }

        if (in_array($code, ['F', 'A', 'P'], true)) {
            return 'First';
        }

        if (in_array($code, ['C', 'J', 'D', 'Z'], true)) {
            return 'Business';
        }

        if (in_array($code, ['W', 'S'], true)) {
            return 'Premium Economy';
        }

        if (in_array($code, ['Y', 'B', 'M', 'H', 'V', 'L', 'G', 'N', 'T', 'E', 'R', 'U', 'Q', 'K', 'X', 'O', 'I'], true)) {
            return 'Economy';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $fare
     */
    public static function fareMatchesSearch(array $fare, string $onwardCabin, ?string $returnCabin, string $tripType): bool
    {
        $onwardCabin = self::normalizeUiLabel($onwardCabin);
        $returnCabin = self::normalizeUiLabel($returnCabin ?? $onwardCabin);
        $components = $fare['fare_rules']['components'] ?? [];

        if ($components !== []) {
            foreach ($components as $index => $component) {
                if (! is_array($component)) {
                    continue;
                }

                $requiredCabin = ($tripType === 'round_trip' && $index === 1)
                    ? $returnCabin
                    : $onwardCabin;

                $componentFamily = self::familyFromSabreCode($component['cabin'] ?? null);

                if ($componentFamily !== $requiredCabin) {
                    return false;
                }
            }

            return true;
        }

        $fareFamily = self::familyFromSabreCode($fare['cabin_code'] ?? null);

        return $fareFamily === $onwardCabin;
    }

    /**
     * @return array{CabinPref: array{Cabin: string, PreferLevel: string}}
     */
    public static function sabreTpaExtension(string $uiLabel): array
    {
        return [
            'CabinPref' => [
                'Cabin' => self::toSabreCode($uiLabel),
                'PreferLevel' => 'Only',
            ],
        ];
    }
}
