<?php

namespace App\Support;

use Carbon\Carbon;

final class FlightPassengerDobValidator
{
    public const ADULT_MIN_AGE = 12;

    public const CHILD_MIN_AGE = 2;

    public const CHILD_MAX_AGE = 11;

    public const INFANT_MIN_DAYS = 7;

    /**
     * @param  array<string, mixed>  $searchParams
     */
    public static function resolveLatestTravelDate(array $searchParams): ?Carbon
    {
        $candidates = [];

        foreach (['departure_date', 'return_date'] as $key) {
            $parsed = self::parseDate($searchParams[$key] ?? null);
            if ($parsed !== null) {
                $candidates[] = $parsed;
            }
        }

        foreach ($searchParams['segments'] ?? [] as $segment) {
            if (! is_array($segment)) {
                continue;
            }

            $parsed = self::parseDate($segment['departure_date'] ?? null);
            if ($parsed !== null) {
                $candidates[] = $parsed;
            }
        }

        if ($candidates === []) {
            return null;
        }

        return collect($candidates)->sort()->last();
    }

    public static function ageOnDate(string $dob, Carbon $referenceDate): int
    {
        $birth = Carbon::parse($dob)->startOfDay();
        $reference = $referenceDate->copy()->startOfDay();

        return (int) $birth->diff($reference)->y;
    }

    public static function daysSinceBirth(Carbon $birthDate, Carbon $referenceDate): int
    {
        return (int) $birthDate->copy()->startOfDay()->diffInDays($referenceDate->copy()->startOfDay());
    }

    /**
     * @param  array<int, array<string, mixed>>  $passengers
     * @param  array<string, mixed>  $searchParams
     * @return array<string, string>
     */
    public static function resolveReferenceDate(array $searchParams = []): Carbon
    {
        return self::resolveLatestTravelDate($searchParams) ?? Carbon::today()->startOfDay();
    }

    public static function validatePassengers(array $passengers, array $searchParams = []): array
    {
        $today = Carbon::today()->startOfDay();
        $referenceDate = self::resolveReferenceDate($searchParams);
        $errors = [];

        foreach ($passengers as $index => $passenger) {
            if (! is_array($passenger)) {
                continue;
            }

            $field = "passengers.{$index}.dob";
            $dob = trim((string) ($passenger['dob'] ?? ''));

            if ($dob === '') {
                $errors[$field] = 'Date of birth is required.';

                continue;
            }

            try {
                $birthDate = Carbon::parse($dob)->startOfDay();
            } catch (\Throwable $e) {
                $errors[$field] = 'Enter a valid date of birth.';

                continue;
            }

            if ($birthDate->gt($today)) {
                $errors[$field] = 'Date of birth cannot be in the future.';

                continue;
            }

            $type = strtoupper(trim((string) ($passenger['type'] ?? 'ADT')));
            $age = self::ageOnDate($dob, $referenceDate);
            $daysOld = self::daysSinceBirth($birthDate, $referenceDate);
            $message = self::ageRuleMessage($type, $age, $daysOld, $referenceDate);

            if ($message !== null) {
                $errors[$field] = $message;
            }
        }

        return $errors;
    }

    public static function ageRuleMessage(string $type, int $age, int $daysOld, ?Carbon $referenceDate = null): ?string
    {
        $travelLabel = self::travelDateLabel($referenceDate);

        return match ($type) {
            'C06', 'CNN', 'CHD' => ($age >= self::CHILD_MIN_AGE && $age <= self::CHILD_MAX_AGE)
                ? null
                : 'Child must be between 2 and 11 years old on the travel date' . $travelLabel . '.',
            'INF' => self::infantRuleMessage($age, $daysOld, $travelLabel),
            default => $age >= self::ADULT_MIN_AGE
                ? null
                : 'Adult must be 12 years or older on the travel date' . $travelLabel . '.',
        };
    }

    private static function infantRuleMessage(int $age, int $daysOld, string $travelLabel): ?string
    {
        if ($daysOld < self::INFANT_MIN_DAYS) {
            return 'Infant must be at least 7 days old (1 week) on the travel date' . $travelLabel . '.';
        }

        if ($age >= self::CHILD_MIN_AGE) {
            return 'Infant must be under 2 years old on the travel date' . $travelLabel . '.';
        }

        return null;
    }

    private static function travelDateLabel(?Carbon $referenceDate): string
    {
        if ($referenceDate === null) {
            return '';
        }

        return ' (' . $referenceDate->format('d M Y') . ')';
    }

    private static function parseDate(mixed $value): ?Carbon
    {
        $value = is_string($value) ? trim($value) : '';

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
