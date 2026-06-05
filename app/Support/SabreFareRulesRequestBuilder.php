<?php

namespace App\Support;

use Carbon\Carbon;

final class SabreFareRulesRequestBuilder
{
    /**
     * @param  array<string, mixed>  $pricingBlock
     * @param  array<string, mixed>  $grouped
     * @return list<array{
     *     route_label: string,
     *     fare_basis: string,
     *     fare_rule: ?string,
     *     airline: string,
     *     origin: string,
     *     destination: string,
     *     departure_date: string
     * }>
     */
    public static function fromPricingBlock(array $pricingBlock, array $grouped, ?string $fallbackDepartureDate = null): array
    {
        $passengerFare = data_get($pricingBlock, 'fare.passengerInfoList.0.passengerInfo', []);
        $fareComponentById = collect($grouped['fareComponentDescs'] ?? [])->keyBy('id');
        $airline = self::resolveAirlineCode($pricingBlock);
        $fallbackDate = self::normalizeDepartureDate($fallbackDepartureDate);
        $requests = [];

        foreach (($passengerFare['fareComponents'] ?? []) as $component) {
            if (! is_array($component)) {
                continue;
            }

            $desc = $fareComponentById->get($component['ref'] ?? null);
            if (! is_array($desc)) {
                continue;
            }

            $fareBasis = trim((string) ($desc['fareBasisCode'] ?? ''));
            $origin = strtoupper(trim((string) ($component['beginAirport'] ?? '')));
            $destination = strtoupper(trim((string) ($component['endAirport'] ?? '')));

            if ($fareBasis === '' || $origin === '' || $destination === '') {
                continue;
            }

            $departureDate = self::normalizeDepartureDate(
                self::stringOrNull($desc['notValidBefore'] ?? null) ?? $fallbackDate
            );

            if ($departureDate === null) {
                continue;
            }

            $requests[] = [
                'route_label' => $origin . ' → ' . $destination,
                'fare_basis' => $fareBasis,
                'fare_rule' => self::stringOrNull($desc['fareRule'] ?? null),
                'airline' => $airline,
                'origin' => $origin,
                'destination' => $destination,
                'departure_date' => $departureDate,
            ];
        }

        return self::uniqueRequests($requests);
    }

    /**
     * @param  array<string, mixed>  $pricingBlock
     */
    private static function resolveAirlineCode(array $pricingBlock): string
    {
        $validating = strtoupper(trim((string) data_get($pricingBlock, 'fare.validatingCarrierCode', '')));
        if ($validating !== '') {
            return $validating;
        }

        $governing = strtoupper(trim((string) data_get($pricingBlock, 'fare.governingCarriers', '')));

        return $governing !== '' ? (strtok($governing, ' ') ?: $governing) : '';
    }

    private static function normalizeDepartureDate(?string $date): ?string
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $requests
     * @return list<array<string, mixed>>
     */
    private static function uniqueRequests(array $requests): array
    {
        $unique = [];

        foreach ($requests as $request) {
            $key = strtolower(json_encode($request) ?: '');

            if (! isset($unique[$key])) {
                $unique[$key] = $request;
            }
        }

        return array_values($unique);
    }

    private static function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
