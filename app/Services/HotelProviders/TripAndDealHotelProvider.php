<?php

namespace App\Services\HotelProviders;

use App\Models\Country;
use App\Models\Province;
use App\Services\HotelProviders\Contracts\HotelProviderInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TripAndDealHotelProvider implements HotelProviderInterface
{
    private const API_UPDATED_URL = 'https://hotelpartnerdataapi.tripindeal.com/api/v1/data/updatedpropertyids';
    private const API_DETAILS_URL = 'https://hotelpartnerdataapi.tripindeal.com/api/v1/data/propertydetails';
    private const DETAILS_CHUNK_SIZE = 80;

    public function key(): string
    {
        return 'trip_and_deal';
    }

    public function search(Province|Country $destination, array $rooms, Request $request): Collection
    {
        $country = $destination instanceof Country ? $destination : $destination->country;
        if (!$country || empty($country->iso_code)) {
            return collect();
        }

        $updatedFrom = $this->resolveUpdatedFrom($request);
        $propertyIds = $this->fetchUpdatedPropertyIds($country->iso_code, $updatedFrom);
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

    private function resolveUpdatedFrom(Request $request): string
    {
        $requested = trim((string) $request->input('trip_updated_from', ''));
        if ($requested !== '') {
            return $requested;
        }

        $checkIn = $request->input('check_in');
        if ($checkIn) {
            try {
                return Carbon::parse($checkIn)->format('d-m-Y');
            } catch (\Exception $e) {
                // fallback below
            }
        }

        return Carbon::now()->startOfYear()->format('d-m-Y');
    }

    private function fetchUpdatedPropertyIds(string $countryCode, string $updatedFrom): array
    {
        try {
            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->retry(2, 2000)
                ->get(self::API_UPDATED_URL, [
                    'countryCode' => $countryCode,
                    'date' => $updatedFrom,
                ]);

            if ($response->failed()) {
                Log::error('TripAndDeal updatedpropertyids API failed', [
                    'country_code' => $countryCode,
                    'date' => $updatedFrom,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $payload = $response->json();
            $ids = $payload['PropertyIDs'] ?? [];
            return is_array($ids) ? $ids : [];
        } catch (\Exception $e) {
            Log::error('TripAndDeal updatedpropertyids API error', [
                'country_code' => $countryCode,
                'date' => $updatedFrom,
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
                $response = Http::timeout(30)
                    ->connectTimeout(10)
                    ->retry(2, 2000)
                    ->post(self::API_DETAILS_URL, [
                        'propertyIds' => array_values($chunk),
                    ]);

                if ($response->failed()) {
                    Log::error('TripAndDeal propertydetails API failed', [
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
                Log::error('TripAndDeal propertydetails API error', [
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
                'provider' => 'TripAndDeal',
                'supplier' => 'Trip and Deal',

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
