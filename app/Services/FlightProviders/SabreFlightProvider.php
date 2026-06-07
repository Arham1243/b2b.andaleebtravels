<?php

namespace App\Services\FlightProviders;

use App\Services\FlightProviders\Contracts\FlightProviderInterface;
use App\Services\FlightProviders\DTO\FlightProviderSearchResult;

class SabreFlightProvider implements FlightProviderInterface
{
    public function __construct(
        private readonly SabreFlightSearchService $searchService = new SabreFlightSearchService(),
    ) {
    }

    public function key(): string
    {
        return 'sabre';
    }

    /**
     * @param  array<string, mixed>  $searchData
     */
    public function search(array $searchData): FlightProviderSearchResult
    {
        $out = $this->searchService->search($searchData);

        return new FlightProviderSearchResult(
            provider: 'sabre',
            results: $out['results'],
            messages: $out['messages'],
            itineraryCount: $out['itineraryCount'],
            rawResponse: $out['rawResponse'],
            requestPayload: $out['requestPayload'],
            success: $out['itineraryCount'] > 0 || $out['results'] !== [],
        );
    }
}
