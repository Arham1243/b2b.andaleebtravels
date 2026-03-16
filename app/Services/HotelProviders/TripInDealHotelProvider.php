<?php

namespace App\Services\HotelProviders;

use App\Models\Country;
use App\Models\Province;
use App\Services\HotelProviders\Contracts\HotelProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TripInDealHotelProvider implements HotelProviderInterface
{
    private const API_IDS_URL = 'https://hotelpartnerdataapi.tripindeal.com/api/v1/data/propertyids';
    private const API_DETAILS_URL = 'https://hotelpartnerdataapi.tripindeal.com/api/v1/data/propertydetails';
    private const DETAILS_CHUNK_SIZE = 40;
    private const DETAILS_TIMEOUT_SECONDS = 60;
    private const API_TOKEN = '4:176b547380-e4ba-4f5c-8e03-e02328dd6b23';

    public function key(): string
    {
        return 'tripindeal';
    }

    public function search(Province|Country $destination, array $rooms, Request $request): Collection
    {
        $country = $destination instanceof Country ? $destination : $destination->country;
        if (!$country || empty($country->iso_code)) {
            return collect();
        }

        $propertyIds = $this->fetchPropertyIds($country->iso_code);
        if (empty($propertyIds)) {
            return collect();
        }

        $details = $this->fetchPropertyDetails($propertyIds);

        if ($destination instanceof Province) {
            $city = strtolower(trim($destination->name));
            $details = $details->filter(function ($item) use ($city) {
                $cityName = strtolower(trim((string) ($item['City'] ?? '')));
                return $cityName !== '' && $cityName === $city;
            });
        }

        return $this->formatHotels($details);
    }

    private function fetchPropertyIds(string $countryCode): array
    {
        try {
            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->retry(2, 2000)
                ->withHeaders(['token' => self::API_TOKEN])
                ->get(self::API_IDS_URL, [
                    'countryCode' => $countryCode,
                ]);

            if ($response->failed()) {
                Log::error('TripInDeal propertyids API failed', [
                    'country_code' => $countryCode,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $payload = $response->json();
            $ids = $payload['PropertyIDs'] ?? [];
            return is_array($ids) ? $ids : [];
        } catch (\Exception $e) {
            Log::error('TripInDeal propertyids API error', [
                'country_code' => $countryCode,
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function fetchPropertyDetails(array $propertyIds): Collection
    {
        $results = collect();

        foreach (array_chunk($propertyIds, self::DETAILS_CHUNK_SIZE) as $chunk) {
            try {
                $response = Http::timeout(self::DETAILS_TIMEOUT_SECONDS)
                    ->connectTimeout(10)
                    ->retry(3, 3000)
                    ->withHeaders(['token' => self::API_TOKEN])
                    ->post(self::API_DETAILS_URL, [
                        'propertyIds' => array_values($chunk),
                    ]);

                if ($response->failed()) {
                    Log::error('TripInDeal propertydetails API failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    continue;
                }

                $payload = $response->json();
                if (is_array($payload)) {
                    $results = $results->merge($payload);
                }
            } catch (\Exception $e) {
                Log::error('TripInDeal propertydetails API error', [
                    'message' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return $results;
    }

    private function formatHotels(Collection $hotels): Collection
    {
        return $hotels->map(function ($item) {
            $images = is_array($item['Images'] ?? null) ? $item['Images'] : [];
            $image = $images[0] ?? null;
            $stateName = trim((string) ($item['StateName'] ?? ''));

            return [
                'id' => null,
                'provider_id' => (string) ($item['PropertyId'] ?? ''),
                'provider' => 'TripInDeal',
                'supplier' => 'TripInDeal',

                'name' => $item['PropertyName'] ?? null,
                'address' => $item['Address'] ?? null,
                'rating' => $item['StarRating'] ?? null,
                'rating_text' => null,

                'province' => $stateName !== '' ? $stateName : null,
                'location' => $item['City'] ?? null,

                'image' => $image,

                'price' => null,

                'boards' => [],

                'property_type' => $item['PropertyType'] ?? null,
            ];
        });
    }

    public function fetchDetails(string $propertyId): ?array
    {
        $payload = $this->fetchPropertyDetails([$propertyId])->first();
        return is_array($payload) ? $payload : null;
    }
}
