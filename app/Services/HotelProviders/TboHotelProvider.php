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
    private const API_USERNAME = 'andaleebTest';
    private const API_PASSWORD = 'And@30524459';
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

        // Page-aware budget — see HotelProviderManager. The city catalogue can
        // contain thousands of hotels for places like Dubai; checking availability
        // for all of them is what makes the page take forever to load. When the
        // earlier providers (Yalago, TripInDeal) already brought enough hotels to
        // cover the page, we only need to top up the remainder.
        $budget = (int) $request->attributes->get('hotel_search_budget', PHP_INT_MAX);
        if ($budget <= 0) {
            return collect();
        }

        if ($budget < PHP_INT_MAX && $hotels->count() > $budget) {
            $hotels = $hotels->take($budget)->values();
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

    /**
     * HotelCodeList vs HotelDetails use different image shapes; listing cards only have catalogue data.
     *
     * @param  array<string,mixed>  $item
     */
    private function imageUrlFromHotelCatalogueRow(array $item): ?string
    {
        $candidates = [
            $item['ImageUrls'] ?? null,
            $item['Images'] ?? null,
        ];

        foreach ($candidates as $urls) {
            if (!is_array($urls) || $urls === []) {
                continue;
            }
            $first = reset($urls);
            if (is_string($first) && $first !== '') {
                return $first;
            }
            if (is_array($first)) {
                $url = $first['ImageUrl'] ?? $first['Url'] ?? $first['url'] ?? null;
                if (is_string($url) && $url !== '') {
                    return $url;
                }
            }
        }

        $single = $item['Image'] ?? $item['HotelPicture'] ?? null;

        return is_string($single) && $single !== '' ? $single : null;
    }

    /**
     * Search availability payload sometimes includes a hotel image when the city catalogue does not.
     *
     * @param  array<string,mixed>  $hotelResult
     */
    private function imageUrlFromSearchHotelResult(array $hotelResult): ?string
    {
        foreach (['HotelPicture', 'HotelImage', 'Image', 'MainImage', 'Thumbnail'] as $key) {
            $v = $hotelResult[$key] ?? null;
            if (is_string($v) && $v !== '') {
                return $v;
            }
        }

        $urls = $hotelResult['Images'] ?? $hotelResult['HotelImages'] ?? null;
        if (!is_array($urls) || $urls === []) {
            return null;
        }
        $first = reset($urls);
        if (is_string($first) && $first !== '') {
            return $first;
        }
        if (is_array($first)) {
            $url = $first['ImageUrl'] ?? $first['Url'] ?? $first['url'] ?? null;

            return is_string($url) && $url !== '' ? $url : null;
        }

        return null;
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
            $hotelCode = (string) ($item['HotelCode'] ?? '');
            $rate = $hotelCode !== '' ? ($rates[$hotelCode] ?? null) : null;
            $catalogueImage = $this->imageUrlFromHotelCatalogueRow(is_array($item) ? $item : []);
            $image = $catalogueImage ?? (is_array($rate) ? ($rate['thumbnail_url'] ?? null) : null);
            $rawPrice = is_array($rate) ? ($rate['total_fare'] ?? null) : null;
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

                'boards' => is_array($rate) && !empty($rate['meal_type'])
                    ? [str_replace('_', ' ', (string) $rate['meal_type'])]
                    : [],

                'tbo_booking_code' => is_array($rate) ? ($rate['booking_code'] ?? null) : null,
                'tbo_room_name' => is_array($rate) ? ($rate['room_name'] ?? null) : null,
                'tbo_room_names' => is_array($rate) ? ($rate['room_names'] ?? []) : [],
                'tbo_currency' => is_array($rate) ? ($rate['currency'] ?? null) : null,
                'tbo_meal_type' => is_array($rate) ? ($rate['meal_type'] ?? null) : null,
                'tbo_is_refundable' => is_array($rate) ? ($rate['is_refundable'] ?? null) : null,
                'tbo_total_fare_raw' => is_array($rate) ? ($rate['total_fare'] ?? null) : null,

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
                // 201 = no rooms for this chunk/criteria - normal during batched search; do not treat as error.
                if ($statusCode === 201) {
                    continue;
                }
                if ($statusCode !== 200) {
                    Log::warning('TBO Hotel Search API unexpected status', [
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

            $hotelResultArr = is_array($hotelResult) ? $hotelResult : [];

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
                'thumbnail_url' => $this->imageUrlFromSearchHotelResult($hotelResultArr),
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
