<?php

namespace App\Services\FlightProviders;

use App\Services\FlightProviders\Contracts\FlightProviderInterface;
use App\Services\FlightProviders\DTO\FlightProviderSearchResult;
use App\Support\FlightSearchResultFilter;
use Illuminate\Support\Facades\Log;

class FlightProviderManager
{
    /** @var FlightProviderInterface[] */
    private array $providers;

    /**
     * @param  FlightProviderInterface[]  $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * @param  array<string, mixed>  $searchData
     * @return array{
     *   results: list<array<string, mixed>>,
     *   messages: list<array{severity: string, text: string}>,
     *   itineraryCount: int,
     *   responses: array<string, mixed|null>,
     *   payloads: array<string, mixed|null>,
     * }
     */
    public function search(array $searchData): array
    {
        $merged = [];
        $messages = [];
        $responses = [];
        $payloads = [];

        foreach ($this->providers as $provider) {
            $key = $provider->key();

            try {
                $result = $provider->search($searchData);
            } catch (\Throwable $e) {
                Log::warning('Flight provider search failed', [
                    'provider' => $key,
                    'message' => $e->getMessage(),
                ]);
                $result = FlightProviderSearchResult::failure(
                    $key,
                    ucfirst($key) . ' search failed: ' . $e->getMessage(),
                );
            }

            $responses[$key] = $result->rawResponse;
            $payloads[$key] = $result->requestPayload;

            foreach ($result->messages as $message) {
                $messages[] = $message;
            }

            foreach ($result->results as $card) {
                $merged[] = $card;
            }
        }

        $merged = FlightSearchResultFilter::apply($merged, $searchData);

        usort($merged, static function (array $a, array $b): int {
            $priceA = (float) ($a['totalPrice'] ?? $a['supplierPrice'] ?? PHP_FLOAT_MAX);
            $priceB = (float) ($b['totalPrice'] ?? $b['supplierPrice'] ?? PHP_FLOAT_MAX);

            return $priceA <=> $priceB;
        });

        $id = 0;
        foreach ($merged as &$card) {
            $id++;
            $card['id'] = $id;
        }
        unset($card);

        return [
            'results' => $merged,
            'messages' => $messages,
            'itineraryCount' => count($merged),
            'responses' => $responses,
            'payloads' => $payloads,
        ];
    }
}
