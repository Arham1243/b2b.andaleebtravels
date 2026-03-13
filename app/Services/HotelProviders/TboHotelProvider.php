<?php

namespace App\Services\HotelProviders;

use App\Models\Country;
use App\Models\Province;
use App\Services\HotelProviders\Contracts\HotelProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TboHotelProvider implements HotelProviderInterface
{
    private const API_URL = 'http://api.tbotechnology.in/TBOHolidays_HotelAPI/TBOHotelCodeList';
    private const API_DETAILS_URL = 'http://api.tbotechnology.in/TBOHolidays_HotelAPI/HotelDetails';
    private const API_USERNAME = 'SkylineexperienceTest';
    private const API_PASSWORD = 'Sky@69774762';

    public function key(): string
    {
        return 'tbo';
    }

    public function search(Province|Country $destination, array $rooms, Request $request): Collection
    {
        if ($destination instanceof Country) {
            return collect();
        }

        if (empty($destination->tbo_code)) {
            return collect();
        }

        $hotels = $this->fetchByCity($destination->tbo_code);
        return $this->formatHotels($hotels, $destination);
    }

    private function fetchByCity(string $cityCode): Collection
    {
        try {
            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->retry(2, 2000)
                ->withBasicAuth(self::API_USERNAME, self::API_PASSWORD)
                ->post(self::API_URL, [
                    'CityCode' => $cityCode,
                    'IsDetailedResponse' => true,
                ]);

            if ($response->failed()) {
                Log::error('TBO HotelCodeList API failed', [
                    'city_code' => $cityCode,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return collect();
            }

            $payload = $response->json();
            $statusCode = $payload['Status']['Code'] ?? null;
            if ($statusCode !== 200) {
                Log::error('TBO HotelCodeList API status not ok', [
                    'city_code' => $cityCode,
                    'status' => $payload['Status'] ?? null,
                ]);
                return collect();
            }

            return collect($payload['Hotels'] ?? []);
        } catch (\Exception $e) {
            Log::error('TBO HotelCodeList API error', [
                'city_code' => $cityCode,
                'message' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    private function formatHotels(Collection $hotels, Province $province): Collection
    {
        $ratingMap = [
            'OneStar' => 1,
            'TwoStar' => 2,
            'ThreeStar' => 3,
            'FourStar' => 4,
            'FiveStar' => 5,
        ];

        return $hotels->map(function ($item) use ($province, $ratingMap) {
            $ratingText = $item['HotelRating'] ?? null;
            $rating = $ratingText && isset($ratingMap[$ratingText]) ? $ratingMap[$ratingText] : null;
            $image = $item['ImageUrls'][0]['ImageUrl'] ?? null;

            return [
                'id' => null,
                'provider_id' => $item['HotelCode'] ?? null,
                'provider' => 'TBO',
                'supplier' => 'TBO',

                'name' => $item['HotelName'] ?? null,
                'address' => $item['Address'] ?? null,
                'rating' => $rating,
                'rating_text' => null,

                'province' => $province->name,
                'location' => $item['CityName'] ?? null,

                'image' => $image,

                'price' => null,

                'boards' => [],

                'property_type' => null,
            ];
        });
    }

    public function fetchDetails(string $hotelCode): ?array
    {
        try {
            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->retry(2, 2000)
                ->withBasicAuth(self::API_USERNAME, self::API_PASSWORD)
                ->post(self::API_DETAILS_URL, [
                    'Hotelcodes' => $hotelCode,
                    'Language' => 'EN',
                ]);

            if ($response->failed()) {
                Log::error('TBO HotelDetails API failed', [
                    'hotel_code' => $hotelCode,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $payload = $response->json();
            $statusCode = $payload['Status']['Code'] ?? null;
            if ($statusCode !== 200) {
                Log::error('TBO HotelDetails API status not ok', [
                    'hotel_code' => $hotelCode,
                    'status' => $payload['Status'] ?? null,
                ]);
                return null;
            }

            $details = $payload['HotelDetails'][0] ?? null;
            return is_array($details) ? $details : null;
        } catch (\Exception $e) {
            Log::error('TBO HotelDetails API error', [
                'hotel_code' => $hotelCode,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
