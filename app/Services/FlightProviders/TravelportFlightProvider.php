<?php

namespace App\Services\FlightProviders;

use App\Services\FlightProviders\Contracts\FlightProviderInterface;
use App\Services\FlightProviders\DTO\FlightProviderSearchResult;
use App\Services\Travelport\TravelportApiClient;
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
        $requestPayload = ['search_data' => $searchData];
        $messages = [];

        // GDS published fares and NDC/branded upsell fares require separate LFS requests.
        $gdsResponse = $this->client->lowFareSearch($searchData, false, false);
        $ndcResponse = $this->client->lowFareSearch($searchData, true, true);

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
}
