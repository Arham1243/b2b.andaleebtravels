<?php

namespace App\Support;

final class FlightSearchResultFilter
{
    /**
     * @param  list<array<string, mixed>>  $results
     * @param  array<string, mixed>  $searchData
     * @return list<array<string, mixed>>
     */
    public static function apply(array $results, array $searchData): array
    {
        $tripType = (string) ($searchData['trip_type'] ?? 'one_way');
        $onwardCabin = FlightCabinPreference::normalizeUiLabel($searchData['onward_cabin_class'] ?? 'Economy');
        $returnCabin = FlightCabinPreference::normalizeUiLabel($searchData['return_cabin_class'] ?? $onwardCabin);
        $directOnly = ! empty($searchData['direct_flight']);

        $filtered = [];

        foreach ($results as $card) {
            if (! is_array($card)) {
                continue;
            }

            if ($directOnly && ! self::isDirectItinerary($card, $tripType)) {
                continue;
            }

            $fareOptions = is_array($card['fare_options'] ?? null) ? $card['fare_options'] : [];

            if ($fareOptions === []) {
                if (FlightCabinPreference::fareMatchesSearch($card, $onwardCabin, $returnCabin, $tripType)) {
                    $filtered[] = $card;
                }

                continue;
            }

            $matchingFares = array_values(array_filter(
                $fareOptions,
                static fn (array $fare): bool => FlightCabinPreference::fareMatchesSearch(
                    $fare,
                    $onwardCabin,
                    $returnCabin,
                    $tripType,
                ),
            ));

            if ($matchingFares === []) {
                continue;
            }

            $filtered[] = self::withPrimaryFare($card, $matchingFares);
        }

        return $filtered;
    }

    /**
     * @param  array<string, mixed>  $card
     */
    public static function isDirectItinerary(array $card, string $tripType = 'one_way'): bool
    {
        $legs = is_array($card['legs'] ?? null) ? $card['legs'] : [];
        if ($legs === []) {
            return false;
        }

        if ($tripType === 'round_trip' && count($legs) !== 2) {
            return false;
        }

        foreach ($legs as $leg) {
            if (! is_array($leg)) {
                return false;
            }

            $segments = is_array($leg['segments'] ?? null) ? $leg['segments'] : [];
            if (count($segments) !== 1) {
                return false;
            }

            $segment = $segments[0];
            if (! is_array($segment)) {
                return false;
            }

            if ((int) ($segment['stop_count'] ?? 0) > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $card
     * @param  list<array<string, mixed>>  $fareOptions
     * @return array<string, mixed>
     */
    private static function withPrimaryFare(array $card, array $fareOptions): array
    {
        usort($fareOptions, static fn (array $a, array $b): int => ((float) ($a['totalPrice'] ?? 0)) <=> ((float) ($b['totalPrice'] ?? 0)));

        $primary = $fareOptions[0];
        $card['fare_options'] = $fareOptions;
        $card['totalPrice'] = (float) ($primary['totalPrice'] ?? $card['totalPrice'] ?? 0);
        $card['supplierPrice'] = $card['totalPrice'];
        $card['fare_brand'] = $primary['fare_brand'] ?? $card['fare_brand'] ?? null;
        $card['baggage_details'] = $primary['baggage_details'] ?? $card['baggage_details'] ?? [];
        $card['baggage_notes'] = $primary['baggage_notes'] ?? $card['baggage_notes'] ?? '';
        $card['fare_rules'] = $primary['fare_rules'] ?? $card['fare_rules'] ?? [];
        $card['non_refundable'] = (bool) ($primary['non_refundable'] ?? $card['non_refundable'] ?? false);
        $card['fare_tags'] = $primary['fare_tags'] ?? $card['fare_tags'] ?? ['published'];
        $card['listing_meta'] = FlightListingMetaBuilder::fromLegs(
            is_array($card['legs'] ?? null) ? $card['legs'] : [],
            $card['totalPrice'],
            ['tags' => is_array($card['fare_tags']) ? $card['fare_tags'] : ['published']],
        );

        return $card;
    }
}
