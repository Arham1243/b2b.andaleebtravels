<?php

namespace App\Services\HotelProviders;

use App\Models\Hotel;
use App\Models\Province;
use App\Services\HotelProviders\Contracts\HotelProviderInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YalagoHotelProvider implements HotelProviderInterface
{
    private const API_KEY = '93082895-c45f-489f-ae10-bed9eaae161e';
    private const API_URL = 'https://api.yalago.com/hotels/availability/get';

    private float $commissionPercentage;

    public function __construct(float $commissionPercentage)
    {
        $this->commissionPercentage = $commissionPercentage;
    }

    public function key(): string
    {
        return 'yalago';
    }

    public function search(Province $province, array $rooms, Request $request): Collection
    {
        $hotelIds = Hotel::where('province_id', $province->id)
            ->whereNotNull('yalago_id')
            ->pluck('yalago_id');

        if ($hotelIds->isEmpty()) {
            return collect();
        }

        $availability = $this->fetchAvailability($hotelIds, $rooms, $request);
        return $this->formatHotels($availability);
    }

    private function fetchAvailability(Collection $hotelIds, array $rooms, Request $request): Collection
    {
        $startDate = Carbon::parse($request->check_in)->format('Y-m-d');
        $endDate = Carbon::parse($request->check_out)->format('Y-m-d');

        $payload = [
            'CheckInDate' => $startDate,
            'CheckOutDate' => $endDate,
            'EstablishmentIds' => $hotelIds->toArray(),
            'Rooms' => $rooms,
            'SourceMarket' => '',
            'Culture' => 'en-GB',
            'GetPackagePrice' => false,
            'GetTaxBreakdown' => true,
            'IsPackage' => false,
            'GetLocalCharges' => true,
            'IsBindingPrice' => true,
        ];

        try {
            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->retry(2, 2000)
                ->withHeaders([
                    'x-api-key' => self::API_KEY,
                    'Accept' => 'application/json',
                ])
                ->post(self::API_URL, $payload);

            if ($response->failed()) {
                Log::error('Yalago API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return collect();
            }

            return collect($response->json()['Establishments'] ?? []);
        } catch (\Exception $e) {
            Log::error('Yalago API timeout/error', [
                'message' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    private function formatHotels(Collection $hotels): Collection
    {
        $yalagoIds = $hotels->pluck('EstablishmentId')->all();

        $localHotels = Hotel::with(['province', 'location'])
            ->whereIn('yalago_id', $yalagoIds)
            ->get()
            ->keyBy('yalago_id');

        return $hotels->map(function ($item) use ($localHotels) {
            $localHotel = $localHotels->get($item['EstablishmentId']);

            $boards = collect($item['Rooms'] ?? [])
                ->flatMap(fn($room) => $room['Boards'] ?? []);

            $cheapestBoard = $boards->sortBy('NetCost.Amount')->first();
            $netAmount = data_get($cheapestBoard, 'NetCost.Amount');

            // decode images if they are JSON string
            $imagesRaw = $localHotel?->images;
            $images = is_string($imagesRaw)
                ? json_decode($imagesRaw, true)
                : $imagesRaw;

            $rating = $localHotel?->rating ?? ($item['EstablishmentInfo']['Rating'] ?? null);

            return [
                'id' => $localHotel?->id,
                'provider_id' => $item['EstablishmentId'] ?? null,
                'provider' => 'Yalago',
                'supplier' => 'Yalago',

                'name' => $localHotel?->name ?? ($item['EstablishmentInfo']['EstablishmentName'] ?? null),
                'address' => $localHotel?->address ?? ($item['EstablishmentInfo']['Address'] ?? null),
                'rating' => $rating,
                'rating_text' => $localHotel?->rating_text,

                'province' => $localHotel?->province?->name,
                'location' => $localHotel?->location?->name,

                'image' => $images[0]['Url'] ?? null,

                'price' => $netAmount !== null
                    ? calculatePriceWithCommission($netAmount, $this->commissionPercentage)
                    : null,

                'boards' => $boards
                    ->pluck('Description')
                    ->unique()
                    ->values()
                    ->all(),

                'property_type' => $item['EstablishmentInfo']['AccomodationType'] ?? null,
            ];
        });
    }
}
