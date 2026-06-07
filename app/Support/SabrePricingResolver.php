<?php

namespace App\Support;

final class SabrePricingResolver
{
    /**
     * @param  array<string, mixed>  $resultCard
     * @param  array<string, mixed>  $grouped
     * @return array<string, mixed>|null
     */
    public static function pricingBlockForFare(array $resultCard, array $grouped, int $fareIndex): ?array
    {
        $fareOptions = $resultCard['fare_options'] ?? [];
        $fareOption = $fareOptions[$fareIndex] ?? null;

        if (! is_array($fareOption)) {
            return null;
        }

        $pricingIndex = (int) ($fareOption['sabre_pricing_index'] ?? 0);
        $sabreItineraryId = (int) ($resultCard['sabre_itinerary_id'] ?? 0);
        $groupIndex = (int) ($resultCard['sabre_group_index'] ?? 0);

        $group = $grouped['itineraryGroups'][$groupIndex] ?? null;
        if (! is_array($group)) {
            return null;
        }

        foreach ($group['itineraries'] ?? [] as $itinerary) {
            if (! is_array($itinerary) || (int) ($itinerary['id'] ?? 0) !== $sabreItineraryId) {
                continue;
            }

            $blocks = self::normalizePricingInformation($itinerary['pricingInformation'] ?? []);

            return $blocks[$pricingIndex]['block'] ?? null;
        }

        return null;
    }

    /**
     * Resolve Sabre pricing from persisted booking itinerary_data + search_response.
     *
     * @param  array<string, mixed>  $card
     * @param  array<string, mixed>  $grouped
     * @return array<string, mixed>|null
     */
    public static function pricingBlockFromStoredCard(array $card, array $grouped): ?array
    {
        $fareIndex = (int) ($card['selected_fare_index'] ?? 0);
        $block = self::pricingBlockForFare($card, $grouped, $fareIndex);
        if ($block !== null) {
            return $block;
        }

        $pricingIndex = (int) ($card['sabre_pricing_index'] ?? 0);
        $sabreItineraryId = (int) ($card['sabre_itinerary_id'] ?? 0);
        $groupIndex = (int) ($card['sabre_group_index'] ?? 0);

        $group = $grouped['itineraryGroups'][$groupIndex] ?? null;
        if (! is_array($group)) {
            return null;
        }

        foreach ($group['itineraries'] ?? [] as $itinerary) {
            if (! is_array($itinerary) || (int) ($itinerary['id'] ?? 0) !== $sabreItineraryId) {
                continue;
            }

            $blocks = self::normalizePricingInformation($itinerary['pricingInformation'] ?? []);

            foreach ($blocks as $entry) {
                if ((int) $entry['index'] === $pricingIndex) {
                    return $entry['block'];
                }
            }

            return $blocks[0]['block'] ?? null;
        }

        return null;
    }

    /**
     * @return list<array{index: int, block: array<string, mixed>}>
     */
    private static function normalizePricingInformation(mixed $pricingInformation): array
    {
        if (! is_array($pricingInformation) || $pricingInformation === []) {
            return [];
        }

        if (array_key_exists('fare', $pricingInformation)) {
            return [['index' => 0, 'block' => $pricingInformation]];
        }

        $entries = [];

        foreach ($pricingInformation as $index => $block) {
            if (! is_array($block) || ! array_key_exists('fare', $block)) {
                continue;
            }

            $entries[] = [
                'index' => (int) $index,
                'block' => $block,
            ];
        }

        return $entries;
    }
}
