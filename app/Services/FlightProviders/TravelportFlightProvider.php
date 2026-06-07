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
        $response = $this->client->lowFareSearch($searchData);

        if (! ($response['success'] ?? false)) {
            return new FlightProviderSearchResult(
                provider: 'travelport',
                messages: [[
                    'severity' => 'Warning',
                    'text' => 'Travelport: ' . ($response['error'] ?? 'Search failed.'),
                ]],
                rawResponse: $response['parsed'],
                requestPayload: $requestPayload,
                success: false,
            );
        }

        $results = TravelportSearchPresenter::toResultCards($response['parsed'], $searchData);

        return new FlightProviderSearchResult(
            provider: 'travelport',
            results: $results,
            messages: $results === [] ? [[
                'severity' => 'Info',
                'text' => 'Travelport returned no itineraries for this search.',
            ]] : [],
            itineraryCount: count($results),
            rawResponse: $response['parsed'],
            requestPayload: $requestPayload,
            success: true,
        );
    }
}
