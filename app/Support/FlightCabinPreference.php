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

    public static function familyFromCabin(?string $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        if ($fromCode = self::familyFromSabreCode($value)) {
            return $fromCode;
        }

        foreach (self::uiOptions() as $option) {
            if (strcasecmp($value, $option) === 0) {
                return $option;
            }
        }

        $normalized = strtolower(str_replace([' ', '_', '-'], '', $value));

        return match ($normalized) {
            'economy', 'coach' => 'Economy',
            'premiumeconomy', 'premium' => 'Premium Economy',
            'business' => 'Business',
            'first', 'firstclass' => 'First',
            default => null,
        };
    }

    public static function familyFromBrandName(?string $brandName): ?string
    {
        $upper = strtoupper(trim((string) ($brandName ?? '')));

        if ($upper === '') {
            return null;
        }

        if (str_contains($upper, 'FIRST')) {
            return 'First';
        }

        if (str_contains($upper, 'FALCON GOLD')
            || str_contains($upper, 'BUSINESS')
            || str_contains($upper, ' BIZ')
            || str_starts_with($upper, 'BIZ ')
            || $upper === 'BIZ') {
            return 'Business';
        }

        if (str_contains($upper, 'PREMIUM')) {
            return 'Premium Economy';
        }

        if (str_contains($upper, 'ECONOMY') || str_contains($upper, 'ECO ')) {
            return 'Economy';
        }

        return null;
    }

    /**
     * Resolve a UI cabin family from Travelport brand, booking class, and optional API cabin.
     */
    public static function resolveCabinFamily(?string $brandName, ?string $bookingCode, ?string $apiCabin = null): string
    {
        $apiFamily = self::familyFromCabin($apiCabin);
        $brandFamily = self::familyFromBrandName($brandName);
        $bookingFamily = self::familyFromSabreCode($bookingCode);

        foreach ([$brandFamily, $bookingFamily] as $candidate) {
            if ($candidate !== null && in_array($candidate, ['Business', 'First'], true)) {
                if ($apiFamily === null || $apiFamily === 'Economy') {
                    return $candidate;
                }
            }
        }

        if ($brandFamily === 'Premium Economy' && ($apiFamily === null || $apiFamily === 'Economy')) {
            return 'Premium Economy';
        }

        if ($brandFamily !== null) {
            return $brandFamily;
        }

        if ($apiFamily !== null) {
            return $apiFamily;
        }

        if ($bookingFamily !== null) {
            return $bookingFamily;
        }

        return 'Economy';
    }

    /**
     * @param  array<string, mixed>  $fare
     */
    public static function reconcileFareCabinFamily(array $fare, ?string $cabinValue): ?string
    {
        $cabinFamily = self::familyFromCabin($cabinValue);

        if ($cabinFamily !== null && $cabinFamily !== 'Economy') {
            return $cabinFamily;
        }

        $brandFamily = self::familyFromBrandName($fare['fare_brand'] ?? null);
        $bookingFamily = self::familyFromSabreCode($fare['booking_code'] ?? null);

        foreach ([$brandFamily, $bookingFamily] as $candidate) {
            if ($candidate !== null && in_array($candidate, ['Business', 'First'], true)) {
                return $candidate;
            }
        }

        if ($brandFamily === 'Premium Economy') {
            return 'Premium Economy';
        }

        return $cabinFamily;
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
            $hasResolvableCabin = false;

            foreach ($components as $index => $component) {
                if (! is_array($component)) {
                    continue;
                }

                $requiredCabin = ($tripType === 'round_trip' && $index === 1)
                    ? $returnCabin
                    : $onwardCabin;

                $componentFamily = self::reconcileFareCabinFamily($fare, $component['cabin'] ?? null);

                if ($componentFamily === null) {
                    continue;
                }

                $hasResolvableCabin = true;

                if ($componentFamily !== $requiredCabin) {
                    return false;
                }
            }

            if ($hasResolvableCabin) {
                return true;
            }
        }

        $fareFamily = self::reconcileFareCabinFamily($fare, $fare['cabin_code'] ?? null);

        if ($fareFamily === $onwardCabin) {
            return true;
        }

        if ($tripType === 'round_trip' && $fareFamily !== null && $fareFamily === $returnCabin && $onwardCabin === $returnCabin) {
            return true;
        }

        return false;
    }

    /**
     * Both flags are required: MultipleBrandedFares alone can cap each itinerary to one brand.
     *
     * @return array{SingleBrandedFare: bool, MultipleBrandedFares: bool}
     */
    public static function sabreBrandedFareIndicators(): array
    {
        return [
            'SingleBrandedFare' => true,
            'MultipleBrandedFares' => true,
        ];
    }

    public static function isClearlyPremiumCabinCode(?string $code): bool
    {
        $family = self::familyFromCabin($code);

        return in_array($family, ['Business', 'First', 'Premium Economy'], true);
    }

    public static function isClearlyEconomyCabinCode(?string $code): bool
    {
        return self::familyFromCabin($code) === 'Economy';
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
