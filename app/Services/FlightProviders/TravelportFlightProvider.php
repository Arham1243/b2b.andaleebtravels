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
     * Load NDC upsell + cross-cabin GDS/NDC fares for one itinerary card.
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
            $card = TravelportSearchPresenter::enrichCardFareOptions($card, $matched['fare_options'] ?? []);
        }

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
        // Full upsell + cross-cabin fares load on demand via loadMoreFares().
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
     * @param  array<string, mixed>  $searchData
     * @return list<string>
     */
    private function moreFaresCabins(array $searchData): array
    {
        $primary = FlightCabinPreference::normalizeUiLabel($searchData['onward_cabin_class'] ?? 'Economy');
        $cabins = [$primary];

        if (! in_array($primary, ['Business', 'First'], true)) {
            $cabins[] = 'Business';
        }

        return array_values(array_unique($cabins));
    }
}
