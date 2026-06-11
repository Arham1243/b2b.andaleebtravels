<?php

namespace App\Services\FlightProviders;

use App\Services\FlightProviders\Contracts\FlightProviderInterface;
use App\Services\FlightProviders\DTO\FlightProviderSearchResult;
use App\Services\Travelport\TravelportApiClient;
use App\Support\FlightCabinPreference;
use App\Support\Travelport\TravelportAirPricePresenter;
use App\Support\Travelport\TravelportHoldPayloadBuilder;
use App\Support\Travelport\TravelportSearchPresenter;

class TravelportFlightProvider implements FlightProviderInterface
{
    public function __construct(
        private readonly TravelportApiClient $client = new TravelportApiClient(),
    ) {
    }

    public function key(): string
    {
        return 'travelport';
    }

    /**
     * @param  array<string, mixed>  $searchData
     */
    public function search(array $searchData): FlightProviderSearchResult
    {
        return $this->executeSearch($searchData, lite: true);
    }

    /**
     * Load additional GDS + NDC upsell fares for one itinerary card in the searched cabin only.
     *
     * @param  array<string, mixed>  $searchData
     * @param  array<string, mixed>  $card
     * @return array<string, mixed>
     */
    public function loadMoreFares(array $searchData, array $card): array
    {
        $signature = TravelportSearchPresenter::routingSignature($card);
        $cabins = $this->moreFaresCabins($searchData);
        $cardLists = [];

        foreach ($cabins as $cabin) {
            $cabinSearch = array_merge($searchData, ['onward_cabin_class' => $cabin]);
            // NDC branded upsell tiers (Saver, Flex). Flex Plus is not in LFS upsell for EK.
            $ndcResponse = $this->client->lowFareSearch($cabinSearch, true, true);

            $ndcCards = ($ndcResponse['success'] ?? false)
                ? TravelportSearchPresenter::toResultCards($ndcResponse['parsed'], $cabinSearch)
                : [];

            if ($ndcCards !== []) {
                $cardLists[] = $this->filterCardsFareOptionsByTag($ndcCards, 'ndc');
            }
        }

        if ($cardLists !== []) {
            $allCards = TravelportSearchPresenter::mergeResultCardLists(...$cardLists);
            $matched = TravelportSearchPresenter::findCardByRoutingSignature($allCards, $signature);

            if ($matched !== null) {
                $incoming = $this->filterFareOptionsBySearchCabins($matched['fare_options'] ?? [], $searchData);
                $card = TravelportSearchPresenter::enrichCardFareOptions($card, $incoming);
            }
        }

        $airPriceOptions = $this->fetchAirPriceFamilyOptions($searchData, $card);

        if ($airPriceOptions !== []) {
            $card = TravelportSearchPresenter::enrichCardFareOptions($card, $airPriceOptions);
            $card = TravelportSearchPresenter::enrichCardFareOptions(
                $card,
                $this->ndcSupplementFromAirPrice($card, $airPriceOptions),
            );
        }

        $filtered = TravelportSearchPresenter::collapseRedundantGdsEconomyFares(
            $this->filterFareOptionsBySearchCabins($card['fare_options'] ?? [], $searchData),
        );
        $card['fare_options'] = [];
        $card = TravelportSearchPresenter::enrichCardFareOptions($card, $filtered);

        $card['travelport_fares_expanded'] = true;

        return $card;
    }

    /**
     * @param  array<string, mixed>  $searchData
     */
    private function executeSearch(array $searchData, bool $lite): FlightProviderSearchResult
    {
        $requestPayload = [
            'search_data' => $searchData,
            'passenger_counts' => TravelportHoldPayloadBuilder::passengerCounts($searchData),
            'lite' => $lite,
        ];
        $messages = [];

        if ($this->isUaeDomesticSearch($searchData)) {
            return new FlightProviderSearchResult(
                provider: 'travelport',
                messages: [[
                    'severity' => 'Info',
                    'text' => 'Travelport does not return UAE domestic routes on this PCC. Showing Sabre results where available.',
                ]],
                requestPayload: array_merge($requestPayload, ['skipped' => 'uae_domestic']),
                success: true,
            );
        }

        // Lite search: published GDS + one branded NDC fare per routing (fast initial list).
        // Additional upsell fares for the same cabin load on demand via loadMoreFares().
        $gdsResponse = $this->client->lowFareSearch($searchData, false, false);
        $ndcResponse = $this->client->lowFareSearch(
            $searchData,
            true,
            $lite ? false : true,
        );

        if (! ($gdsResponse['success'] ?? false) && ! ($ndcResponse['success'] ?? false)) {
            $error = $ndcResponse['error'] ?? $gdsResponse['error'] ?? 'Search failed.';

            return new FlightProviderSearchResult(
                provider: 'travelport',
                messages: [[
                    'severity' => 'Warning',
                    'text' => 'Travelport: ' . $error,
                ]],
                rawResponse: [
                    'gds' => $gdsResponse['parsed'] ?? null,
                    'ndc' => $ndcResponse['parsed'] ?? null,
                ],
                requestPayload: $requestPayload,
                success: true,
            );
        }

        if (! ($gdsResponse['success'] ?? false)) {
            $messages[] = [
                'severity' => 'Warning',
                'text' => 'Travelport GDS fares unavailable: ' . ($gdsResponse['error'] ?? 'Search failed.'),
            ];
        }

        if (! ($ndcResponse['success'] ?? false)) {
            $messages[] = [
                'severity' => 'Warning',
                'text' => 'Travelport NDC fares unavailable: ' . ($ndcResponse['error'] ?? 'Search failed.'),
            ];
        }

        $gdsCards = ($gdsResponse['success'] ?? false)
            ? TravelportSearchPresenter::toResultCards($gdsResponse['parsed'], $searchData)
            : [];
        $ndcCards = ($ndcResponse['success'] ?? false)
            ? TravelportSearchPresenter::toResultCards($ndcResponse['parsed'], $searchData)
            : [];

        $results = TravelportSearchPresenter::mergeResultCardLists($gdsCards, $ndcCards);
        foreach ($results as &$card) {
            $card['travelport_fares_expanded'] = ! $lite;
        }
        unset($card);

        return new FlightProviderSearchResult(
            provider: 'travelport',
            results: $results,
            messages: $results === []
                ? array_merge($messages, [[
                    'severity' => 'Info',
                    'text' => 'Travelport returned no itineraries for this search.',
                ]])
                : $messages,
            itineraryCount: count($results),
            rawResponse: [
                'gds' => $gdsResponse['parsed'] ?? null,
                'ndc' => $ndcResponse['parsed'] ?? null,
            ],
            requestPayload: $requestPayload,
            success: true,
        );
    }

    /**
     * GDS branded tiers (Saver / Flex / Flex Plus) via AirPrice FareFamily — not returned by LFS GDS shop.
     *
     * @param  array<string, mixed>  $searchData
     * @param  array<string, mixed>  $card
     * @return list<array<string, mixed>>
     */
    private function fetchAirPriceFamilyOptions(array $searchData, array $card): array
    {
        $segments = TravelportHoldPayloadBuilder::buildAirPriceSegments($card);
        if ($segments === []) {
            return [];
        }

        $airPriceResponse = $this->client->airPriceFareFamily(
            $segments,
            TravelportHoldPayloadBuilder::passengerCounts($searchData),
            $searchData,
        );

        if (! ($airPriceResponse['success'] ?? false)) {
            return [];
        }

        $legs = is_array($card['legs'] ?? null) ? $card['legs'] : [];

        return TravelportAirPricePresenter::toFareOptions(
            $airPriceResponse['parsed'],
            $searchData,
            $legs,
        );
    }

    /**
     * NDC upsell from LFS omits some tiers (e.g. ECO FLEXPLUS). Re-offer them with an NDC tag.
     *
     * @param  array<string, mixed>  $card
     * @param  list<array<string, mixed>>  $airPriceOptions
     * @return list<array<string, mixed>>
     */
    private function ndcSupplementFromAirPrice(array $card, array $airPriceOptions): array
    {
        $existingNdcBases = [];
        foreach ($card['fare_options'] ?? [] as $option) {
            if (! is_array($option) || ! $this->fareOptionHasTag($option, 'ndc')) {
                continue;
            }

            $basis = strtoupper(trim((string) ($option['fare_basis'] ?? '')));
            if ($basis !== '') {
                $existingNdcBases[$basis] = true;
            }
        }

        $supplement = [];
        foreach ($airPriceOptions as $option) {
            if (! is_array($option)) {
                continue;
            }

            $basis = strtoupper(trim((string) ($option['fare_basis'] ?? '')));
            if ($basis === '' || isset($existingNdcBases[$basis])) {
                continue;
            }

            $copy = $option;
            $copy['fare_tags'] = ['published', 'ndc'];
            $supplement[] = $copy;
        }

        return $supplement;
    }

    /**
     * @param  list<array<string, mixed>>  $cards
     * @return list<array<string, mixed>>
     */
    private function filterCardsFareOptionsByTag(array $cards, string $tag): array
    {
        $filtered = [];
        foreach ($cards as $card) {
            if (! is_array($card)) {
                continue;
            }

            $copy = $card;
            $copy['fare_options'] = array_values(array_filter(
                $card['fare_options'] ?? [],
                fn (array $option): bool => $this->fareOptionHasTag($option, $tag),
            ));
            $filtered[] = $copy;
        }

        return $filtered;
    }

    /**
     * @param  array<string, mixed>  $option
     */
    private function fareOptionHasTag(array $option, string $tag): bool
    {
        $tags = is_array($option['fare_tags'] ?? null) ? $option['fare_tags'] : [];

        return in_array(strtolower($tag), array_map('strtolower', $tags), true);
    }

    /**
     * Cabins to request when loading more fares — matches the user's search filters only.
     *
     * @param  array<string, mixed>  $searchData
     * @return list<string>
     */
    private function moreFaresCabins(array $searchData): array
    {
        $cabins = [
            FlightCabinPreference::normalizeUiLabel($searchData['onward_cabin_class'] ?? 'Economy'),
        ];

        if (($searchData['trip_type'] ?? '') === 'round_trip') {
            $returnCabin = FlightCabinPreference::normalizeUiLabel(
                $searchData['return_cabin_class'] ?? ($searchData['onward_cabin_class'] ?? 'Economy'),
            );

            if (! in_array($returnCabin, $cabins, true)) {
                $cabins[] = $returnCabin;
            }
        }

        return $cabins;
    }

    /**
     * @param  list<array<string, mixed>>  $fareOptions
     * @param  array<string, mixed>  $searchData
     * @return list<array<string, mixed>>
     */
    private function filterFareOptionsBySearchCabins(array $fareOptions, array $searchData): array
    {
        $filtered = array_values(array_filter(
            $fareOptions,
            static fn (array $option): bool => FlightCabinPreference::fareMatchesSearch(
                $option,
                $searchData['onward_cabin_class'] ?? 'Economy',
                $searchData['return_cabin_class'] ?? null,
                (string) ($searchData['trip_type'] ?? 'one_way'),
            ),
        ));

        usort($filtered, static fn (array $a, array $b): int => ((float) ($a['totalPrice'] ?? 0)) <=> ((float) ($b['totalPrice'] ?? 0)));

        foreach ($filtered as $index => &$option) {
            $option['travelport_pricing_index'] = $index;
        }
        unset($option);

        return $filtered;
    }

    /**
     * This Travelport PCC returns NO AVAILABILITY for UAE domestic markets.
     * Skip LFS so Sabre results are not hidden behind a hard provider failure.
     *
     * @param  array<string, mixed>  $searchData
     */
    private function isUaeDomesticSearch(array $searchData): bool
    {
        $uaeAirports = ['DXB', 'DWC', 'AUH', 'SHJ', 'AAN', 'RKT', 'FJR', 'XNB'];
        $tripType = (string) ($searchData['trip_type'] ?? 'one_way');

        if ($tripType === 'multi_city') {
            foreach ($searchData['segments'] ?? [] as $segment) {
                if (! is_array($segment)) {
                    continue;
                }

                $from = strtoupper(trim((string) ($segment['from'] ?? '')));
                $to = strtoupper(trim((string) ($segment['to'] ?? '')));

                if ($from !== '' && $to !== '' && $this->isUaeDomesticPair($from, $to, $uaeAirports)) {
                    return true;
                }
            }

            return false;
        }

        $from = strtoupper(trim((string) ($searchData['from'] ?? '')));
        $to = strtoupper(trim((string) ($searchData['to'] ?? '')));

        return $this->isUaeDomesticPair($from, $to, $uaeAirports);
    }

    /**
     * @param  list<string>  $uaeAirports
     */
    private function isUaeDomesticPair(string $from, string $to, array $uaeAirports): bool
    {
        return in_array($from, $uaeAirports, true) && in_array($to, $uaeAirports, true);
    }
}
