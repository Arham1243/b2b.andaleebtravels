<?php

namespace App\Support\Travelport;

use App\Models\B2bFlightBooking;

final class TravelportStoredFareRuleResolver
{
    /**
     * @return array<string, mixed>|null
     */
    public static function resolveFromBooking(B2bFlightBooking $booking): ?array
    {
        $itineraryData = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];

        $fromItinerary = self::normalizeRuleRequest($itineraryData['travelport_fare_rule'] ?? null);
        if ($fromItinerary !== null) {
            return $fromItinerary;
        }

        $searchResponse = is_array($booking->search_response) ? $booking->search_response : [];
        if ($searchResponse === []) {
            return null;
        }

        return self::resolveFromSearchResponse($searchResponse, $itineraryData);
    }

    /**
     * @param  array<string, mixed>  $searchResponse
     * @param  array<string, mixed>  $itineraryData
     * @return array<string, mixed>|null
     */
    public static function resolveFromSearchResponse(array $searchResponse, array $itineraryData): ?array
    {
        $fareIndex = (int) ($itineraryData['selected_fare_index'] ?? 0);
        $fareOptions = $itineraryData['fare_options'] ?? null;

        if (is_array($fareOptions)) {
            $option = $fareOptions[$fareIndex] ?? $fareOptions[0] ?? null;
            if (is_array($option)) {
                $fromOption = self::normalizeRuleRequest($option['travelport_fare_rule'] ?? null);
                if ($fromOption !== null) {
                    return $fromOption;
                }
            }
        }

        $pricePointKey = trim((string) ($itineraryData['travelport_price_point_key'] ?? ''));
        if ($pricePointKey === '' && is_array($fareOptions)) {
            $option = $fareOptions[$fareIndex] ?? $fareOptions[0] ?? null;
            $pricePointKey = trim((string) (is_array($option) ? ($option['travelport_price_point_key'] ?? '') : ''));
        }

        if ($pricePointKey === '') {
            return null;
        }

        $legs = is_array($itineraryData['legs'] ?? null) ? $itineraryData['legs'] : [];
        if ($legs === []) {
            return null;
        }

        return TravelportSearchPresenter::fareRuleRequestForPricePoint($searchResponse, $pricePointKey, $legs);
    }

    /**
     * @param  array<string, mixed>  $storedRules
     * @return list<array<string, mixed>>
     */
    public static function componentsFromStoredSummary(array $storedRules): array
    {
        $policySections = is_array($storedRules['policy_sections'] ?? null) ? $storedRules['policy_sections'] : [];
        if ($policySections === []) {
            return [];
        }

        $sections = [];
        foreach ($policySections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $items = array_values(array_filter(array_map(
                static fn ($item) => trim((string) $item),
                (array) ($section['items'] ?? []),
            )));

            if ($items === []) {
                continue;
            }

            $sections[] = [
                'title' => trim((string) ($section['title'] ?? 'Policy')),
                'paragraphs' => $items,
            ];
        }

        if ($sections === []) {
            return [];
        }

        $fareBasis = trim((string) ($storedRules['components'][0]['fare_basis'] ?? ''));

        return [[
            'route' => 'Fare rules (from search)',
            'fare_basis' => $fareBasis,
            'sections' => $sections,
            'text' => '',
        ]];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function normalizeRuleRequest(mixed $ruleRequest): ?array
    {
        if (! is_array($ruleRequest)) {
            return null;
        }

        if (trim((string) ($ruleRequest['fare_rule_key'] ?? '')) === '') {
            return null;
        }

        return $ruleRequest;
    }
}
