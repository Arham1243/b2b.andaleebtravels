<?php

namespace App\Services\FlightProviders;

use App\Services\FlightProviders\Contracts\FlightProviderInterface;
use App\Services\FlightProviders\DTO\FlightProviderSearchResult;
use App\Services\Travelport\TravelportApiClient;
use App\Support\FlightCabinPreference;
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
            $gdsResponse = $this->client->lowFareSearch($cabinSearch, false, false);
            $ndcResponse = $this->client->lowFareSearch($cabinSearch, true, true);

            $gdsCards = ($gdsResponse['success'] ?? false)
                ? TravelportSearchPresenter::toResultCards($gdsResponse['parsed'], $cabinSearch)
                : [];
            $ndcCards = ($ndcResponse['success'] ?? false)
                ? TravelportSearchPresenter::toResultCards($ndcResponse['parsed'], $cabinSearch)
                : [];

            if ($gdsCards !== [] || $ndcCards !== []) {
                $cardLists[] = TravelportSearchPresenter::mergeResultCardLists($gdsCards, $ndcCards);
            }
        }

        if ($cardLists === []) {
            $card['travelport_fares_expanded'] = true;

            return $card;
        }

        $allCards = TravelportSearchPresenter::mergeResultCardLists(...$cardLists);
        $matched = TravelportSearchPresenter::findCardByRoutingSignature($allCards, $signature);

        if ($matched !== null) {
            $incoming = $this->filterFareOptionsBySearchCabins($matched['fare_options'] ?? [], $searchData);
            $card = TravelportSearchPresenter::enrichCardFareOptions($card, $incoming);
        }

        $filtered = $this->filterFareOptionsBySearchCabins($card['fare_options'] ?? [], $searchData);
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
        $requestPayload = ['search_data' => $searchData, 'lite' => $lite];
        $messages = [];

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
                rawResponse: null,
                requestPayload: $requestPayload,
                success: false,
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
        $allowed = $this->moreFaresCabins($searchData);

        $filtered = array_values(array_filter($fareOptions, static function (array $option) use ($allowed): bool {
            $cabin = FlightCabinPreference::normalizeUiLabel((string) ($option['cabin_code'] ?? 'Economy'));

            return in_array($cabin, $allowed, true);
        }));

        usort($filtered, static fn (array $a, array $b): int => ((float) ($a['totalPrice'] ?? 0)) <=> ((float) ($b['totalPrice'] ?? 0)));

        foreach ($filtered as $index => &$option) {
            $option['travelport_pricing_index'] = $index;
        }
        unset($option);

        return $filtered;
    }
}
