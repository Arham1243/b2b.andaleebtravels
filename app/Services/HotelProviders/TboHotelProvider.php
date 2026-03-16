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
    private const API_SEARCH_URL = 'http://api.tbotechnology.in/TBOHolidays_HotelAPI/Search';
    private const API_DETAILS_URL = 'http://api.tbotechnology.in/TBOHolidays_HotelAPI/HotelDetails';
    private const API_USERNAME = 'SkylineexperienceTest';
    private const API_PASSWORD = 'Sky@69774762';
    private const SEARCH_CHUNK_SIZE = 100;

    private float $commissionPercentage;

    public function __construct(float $commissionPercentage = 0.0)
    {
        $this->commissionPercentage = $commissionPercentage;
    }

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
        if ($hotels->isEmpty()) {
            return collect();
        }

        $checkIn = $request->input('check_in');
        $checkOut = $request->input('check_out');

        $checkInDate = $checkIn ? \Carbon\Carbon::parse($checkIn)->format('Y-m-d') : null;
        $checkOutDate = $checkOut ? \Carbon\Carbon::parse($checkOut)->format('Y-m-d') : null;

        if (!$checkInDate || !$checkOutDate) {
            return $this->formatHotels($hotels, $destination, collect());
        }

        $guestNationality = (string) $request->input('guest_nationality', 'AE');
        $rates = $this->fetchAvailability($hotels, $rooms, $checkInDate, $checkOutDate, $guestNationality);

        return $this->formatHotels($hotels, $destination, $rates);
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

    private function formatHotels(Collection $hotels, Province $province, Collection $rates): Collection
    {
        $ratingMap = [
            'OneStar' => 1,
            'TwoStar' => 2,
            'ThreeStar' => 3,
            'FourStar' => 4,
            'FiveStar' => 5,
        ];

        return $hotels->map(function ($item) use ($province, $ratingMap, $rates) {
            $ratingText = $item['HotelRating'] ?? null;
            $rating = $ratingText && isset($ratingMap[$ratingText]) ? $ratingMap[$ratingText] : null;
            $image = $item['ImageUrls'][0]['ImageUrl'] ?? null;
            $hotelCode = (string) ($item['HotelCode'] ?? '');
            $rate = $hotelCode !== '' ? ($rates[$hotelCode] ?? null) : null;
            $rawPrice = $rate['total_fare'] ?? null;
            $finalPrice = $rawPrice !== null
                ? calculatePriceWithCommission((float) $rawPrice, $this->commissionPercentage)
                : null;

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

                'price' => $finalPrice,

                'boards' => !empty($rate['meal_type'])
                    ? [str_replace('_', ' ', (string) $rate['meal_type'])]
                    : [],

                'tbo_booking_code' => $rate['booking_code'] ?? null,
                'tbo_room_name' => $rate['room_name'] ?? null,
                'tbo_room_names' => $rate['room_names'] ?? [],
                'tbo_currency' => $rate['currency'] ?? null,
                'tbo_meal_type' => $rate['meal_type'] ?? null,
                'tbo_is_refundable' => $rate['is_refundable'] ?? null,
                'tbo_total_fare_raw' => $rate['total_fare'] ?? null,

                'property_type' => null,
            ];
        });
    }

    private function fetchAvailability(
        Collection $hotels,
        array $rooms,
        string $checkInDate,
        string $checkOutDate,
        string $guestNationality
    ): Collection {
        $hotelCodes = $hotels
            ->pluck('HotelCode')
            ->filter()
            ->map(fn($code) => (string) $code)
            ->unique()
            ->values()
            ->all();

        $paxRooms = collect($rooms)->map(function ($room) {
            $childAges = $room['ChildAges'] ?? [];
            $childrenCount = is_array($childAges) ? count($childAges) : 0;

            $payload = [
                'Adults' => (int) ($room['Adults'] ?? 1),
                'Children' => $childrenCount,
            ];

            if ($childrenCount > 0) {
                $payload['ChildrenAges'] = array_values($childAges);
            }

            return $payload;
        })->values()->all();

        $results = collect();

        foreach (array_chunk($hotelCodes, self::SEARCH_CHUNK_SIZE) as $chunk) {
            try {
                $response = Http::timeout(30)
                    ->connectTimeout(10)
                    ->retry(2, 2000)
                    ->withBasicAuth(self::API_USERNAME, self::API_PASSWORD)
                    ->post(self::API_SEARCH_URL, [
                        'CheckIn' => $checkInDate,
                        'CheckOut' => $checkOutDate,
                        'HotelCodes' => implode(',', $chunk),
                        'GuestNationality' => $guestNationality,
                        'PaxRooms' => $paxRooms,
                        'ResponseTime' => 23.0,
                        'IsDetailedResponse' => false,
                        'Filters' => [
                            'Refundable' => false,
                            'NoOfRooms' => count($rooms),
                            'MealType' => 'All',
                        ],
                    ]);

                if ($response->failed()) {
                    Log::error('TBO Hotel Search API failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    continue;
                }

                $payload = $response->json();
                $statusCode = $payload['Status']['Code'] ?? null;
                if ($statusCode !== 200) {
                    Log::error('TBO Hotel Search API status not ok', [
                        'status' => $payload['Status'] ?? null,
                    ]);
                    continue;
                }

                $hotelResults = $payload['HotelResult'] ?? [];
                if (is_array($hotelResults)) {
                    $results = $results->merge($hotelResults);
                }
            } catch (\Exception $e) {
                Log::error('TBO Hotel Search API error', [
                    'message' => $e->getMessage(),
                ]);
                continue;
            }
        }

        $rates = collect();

        foreach ($results as $hotelResult) {
            $hotelCode = (string) ($hotelResult['HotelCode'] ?? '');
            if ($hotelCode === '') {
                continue;
            }

            $rooms = collect($hotelResult['Rooms'] ?? []);
            if ($rooms->isEmpty()) {
                continue;
            }

            $bestRoom = $rooms->sortBy(function ($room) {
                $fare = $room['TotalFare'] ?? null;
                if ($fare !== null) {
                    return (float) $fare;
                }

                return (float) ($room['TotalTax'] ?? 0);
            })->first();

            if (!$bestRoom) {
                continue;
            }

            $totalFare = $bestRoom['TotalFare'] ?? null;
            if ($totalFare === null && isset($bestRoom['TotalTax'])) {
                $totalFare = (float) $bestRoom['TotalTax'];
            }

            $rates[$hotelCode] = [
                'booking_code' => $bestRoom['BookingCode'] ?? null,
                'room_name' => isset($bestRoom['Name']) && is_array($bestRoom['Name'])
                    ? ($bestRoom['Name'][0] ?? null)
                    : ($bestRoom['Name'] ?? null),
                'room_names' => isset($bestRoom['Name']) && is_array($bestRoom['Name'])
                    ? array_values($bestRoom['Name'])
                    : (isset($bestRoom['Name']) ? [$bestRoom['Name']] : []),
                'total_fare' => $totalFare,
                'currency' => $hotelResult['Currency'] ?? null,
                'meal_type' => $bestRoom['MealType'] ?? null,
                'is_refundable' => $bestRoom['IsRefundable'] ?? null,
            ];
        }

        return $rates;
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
