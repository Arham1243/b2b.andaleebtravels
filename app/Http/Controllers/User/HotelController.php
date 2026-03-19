<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\B2bHotelBooking;
use App\Models\B2bWalletLedger;
use App\Models\Hotel;
use App\Models\Config;
use App\Models\Country;
use App\Models\Province;
use App\Services\HotelProviders\HotelProviderManager;
use App\Services\HotelProviders\TboHotelProvider;
use App\Services\HotelProviders\TripInDealHotelProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class HotelController extends Controller
{
    protected $hotelCommissionPercentage;
    protected ?array $enabledHotelProviders = null;
    public function __construct()
    {
        $config = Config::pluck('config_value', 'config_key')->toArray();
        $this->hotelCommissionPercentage = $config['HOTEL_COMMISSION_PERCENTAGE'] ?? 10;
        $this->enabledHotelProviders = $this->parseProviderConfig($config['HOTEL_SEARCH_PROVIDERS'] ?? null);
    }

    public function index()
    {
        return view('user.hotels.index');
    }

    public function searchHotels(Request $request)
    {
        $q = $request->input('q');

        $hotels = Hotel::with(['country', 'province', 'location'])
            ->where('name', 'like', "%{$q}%")
            ->get()
            ->map(function ($hotel) {
                return [
                    'name' => $hotel->name,
                    'country_name' => $hotel->country?->name,
                    'province_name' => $hotel->province?->name,
                    'location_name' => $hotel->location?->name,
                ];
            });

        return response()->json($hotels);
    }

    public function search(Request $request)
    {

        if (!$request->has(['destination', 'check_in', 'check_out', 'room_count'])) {
            return redirect()->route('user.hotels.index')
                ->with('notify_error', 'Missing required parameters.');
        }


        // 1. Build rooms array
        $rooms = $this->buildRoomsArray($request);

        // 2. Resolve destination to province or country
        [$province, $country] = $this->resolveDestination($request);

        $hotels = collect();
        $perPage = 10;
        $request->merge(['per_page' => $perPage]);

        $tripInDealOnly = is_array($this->enabledHotelProviders)
            && count($this->enabledHotelProviders) === 1
            && $this->enabledHotelProviders[0] === 'tripindeal';

        if ($tripInDealOnly) {
            $request->attributes->set('tripindeal_only', true);
        }

        if ($province) {
            $providerManager = new HotelProviderManager($this->hotelCommissionPercentage, $this->enabledHotelProviders);
            $hotels = $providerManager->search($province, $rooms, $request);
        } elseif ($country) {
            $providerManager = new HotelProviderManager($this->hotelCommissionPercentage, $this->enabledHotelProviders);
            $hotels = $providerManager->search($country, $rooms, $request);
        }

        if ($hotels->isNotEmpty()) {
            // 3. Apply filters
            $hotels = $this->applyFilters($hotels, $request);

            // 4. Apply sorting
            $hotels = $this->applySorting($hotels, $request);
        }

        $this->storeTboSearchRates($hotels);

        // Paginate results
        $page = max(1, (int) $request->input('page', 1));
        $tripInDealPaginated = (bool) $request->attributes->get('tripindeal_paginated', false);
        $total = $hotels->count();
        $pagedHotels = $hotels->values();

        if (!$tripInDealPaginated) {
            $pagedHotels = $pagedHotels->forPage($page, $perPage)->values();
        } else {
            $tripInDealTotal = (int) $request->attributes->get('tripindeal_total', 0);
            if (!$this->hasSearchFilters($request) && $tripInDealTotal > 0) {
                $total = $tripInDealTotal;
            }
        }

        $hasMore = ($page * $perPage) < $total;

        $availableSuppliers = $this->getEnabledProviderLabels();

        return view('user.hotels.search', [
            'hotels' => $pagedHotels,
            'totalHotels' => $total,
            'currentPage' => $page,
            'perPage' => $perPage,
            'hasMore' => $hasMore,
            'availableSuppliers' => $availableSuppliers,
        ]);
    }

    protected function buildRoomsArray(Request $request)
    {
        $rooms = [];
        for ($i = 1; $i <= $request->room_count; $i++) {
            $adults = (int) $request->input("room_{$i}_adults", 0);
            $childrenCount = (int) $request->input("room_{$i}_children", 0);
            $childAges = [];
            for ($c = 1; $c <= $childrenCount; $c++) {
                $age = $request->input("room_{$i}_child_age_{$c}");
                if ($age !== null) $childAges[] = (int) $age;
            }
            $rooms[] = ['Adults' => $adults, 'ChildAges' => $childAges];
        }
        return $rooms;
    }

    // 3. Resolve Destination to Hotel IDs
    protected function resolveDestination(Request $request): array
    {
        $destination = trim((string) $request->destination);
        $destinationType = strtolower(trim((string) $request->input('destination_type', '')));
        $province = null;
        $country = null;

        if ($destinationType === 'province' || $destinationType === 'city') {
            $province = Province::with('country')
                ->where('name', $destination)
                ->where('status', 'active')
                ->first();
        } elseif ($destinationType === 'country') {
            $country = Country::where('name', $destination)->first();
        } else {
            $province = Province::with('country')
                ->where('name', $destination)
                ->where('status', 'active')
                ->first();

            if (!$province) {
                $country = Country::where('name', $destination)->first();
            }
        }

        return [$province, $country];
    }

    // 5. Apply all filters
    protected function applyFilters($hotels, Request $request)
    {
        // Price range
        if ($request->filled('min_price') || $request->filled('max_price')) {
            $min = $request->min_price;
            $max = $request->max_price;
            $hotels = $hotels->filter(function ($hotel) use ($min, $max) {
                $price = $hotel['price'] ?? null;
                if ($price === null) return false;
                return (!$min || $price >= $min) && (!$max || $price <= $max);
            });
        }

        // Rating exact
        if ($request->filled('rating')) {
            $ratings = explode(',', $request->rating);
            $hotels = $hotels->filter(function ($hotel) use ($ratings) {
                $rating = $hotel['rating'] ?? null;
                return $rating !== null && in_array((string) $rating, $ratings, true);
            });
        }

        // Rating range
        $rMin = $request->input('rating_range_min');
        $rMax = $request->input('rating_range_max');
        if ($rMin || $rMax) {
            $hotels = $hotels->filter(
                function ($hotel) use ($rMin, $rMax) {
                    $rating = $hotel['rating'] ?? null;
                    if ($rating === null) return false;
                    return (!$rMin || $rating >= $rMin) && (!$rMax || $rating <= $rMax);
                }
            );
        }

        // Property type
        if ($request->filled('property_type')) {
            $types = explode(',', $request->property_type);
            $hotels = $hotels->filter(function ($hotel) use ($types) {
                $type = $hotel['property_type'] ?? null;
                return $type !== null && in_array($type, $types);
            });
        }

        // Hotel name
        if ($request->filled('hotel_name')) {
            $name = $request->hotel_name;
            $hotels = $hotels->filter(
                fn($hotel) => stripos($hotel['name'] ?? '', $name) !== false
            );
        }

        // Board type
        if ($request->filled('board_type')) {
            $boards = explode(',', $request->board_type);
            $hotels = $hotels->filter(function ($hotel) use ($boards) {
                $hotelBoards = $hotel['boards'] ?? [];
                return count(array_intersect($boards, $hotelBoards)) > 0;
            });
        }

        // Supplier
        if ($request->filled('supplier')) {
            $suppliers = array_map('strtolower', explode(',', $request->supplier));
            $hotels = $hotels->filter(function ($hotel) use ($suppliers) {
                $supplier = strtolower($hotel['supplier'] ?? '');
                return $supplier !== '' && in_array($supplier, $suppliers, true);
            });
        }

        return $hotels;
    }

    // 6. Apply sorting
    protected function applySorting($hotels, Request $request)
    {
        $sort = $request->sort_by;
        $priceValue = function ($hotel) {
            $price = $hotel['price'] ?? null;
            return $price === null ? PHP_FLOAT_MAX : $price;
        };

        if (!$sort || $sort === 'price_low_to_high') {
            $hotels = $hotels->sortBy($priceValue);
        } elseif ($sort === 'price_high_to_low') {
            $hotels = $hotels->sortByDesc($priceValue);
        } elseif ($sort === 'recommended') {
            $hotels = $hotels->filter(fn($hotel) => ($hotel['rating'] ?? 0) == 5);
        } elseif ($sort === 'top_rated') {
            $hotels = $hotels->filter(fn($hotel) => in_array((int) ($hotel['rating'] ?? 0), [5, 4], true));
        }

        return $hotels;
    }

    private function storeTboSearchRates(Collection $hotels): void
    {
        $tboRates = $hotels
            ->filter(fn($hotel) => strtoupper((string) ($hotel['supplier'] ?? '')) === 'TBO')
            ->filter(fn($hotel) => !empty($hotel['provider_id']) && !empty($hotel['tbo_booking_code']))
            ->mapWithKeys(function ($hotel) {
                return [
                    (string) $hotel['provider_id'] => [
                        'booking_code' => $hotel['tbo_booking_code'] ?? null,
                        'price' => $hotel['price'] ?? null,
                        'currency' => $hotel['tbo_currency'] ?? null,
                        'room_name' => $hotel['tbo_room_name'] ?? null,
                        'room_names' => $hotel['tbo_room_names'] ?? [],
                        'meal_type' => $hotel['tbo_meal_type'] ?? null,
                        'is_refundable' => $hotel['tbo_is_refundable'] ?? null,
                        'total_fare_raw' => $hotel['tbo_total_fare_raw'] ?? null,
                    ],
                ];
            })
            ->toArray();

        session(['tbo_search_rates' => $tboRates]);
    }
    private function parseProviderConfig($raw): ?array
    {
        if (empty($raw)) {
            return null;
        }

        $providers = [];

        if (is_array($raw)) {
            $providers = $raw;
        } elseif (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $providers = $decoded;
            } else {
                $providers = array_map('trim', explode(',', $raw));
            }
        }

        $providers = array_values(array_unique(array_filter(array_map(function ($value) {
            return strtolower(trim((string) $value));
        }, $providers))));

        $allowed = ['yalago', 'tbo', 'tripindeal'];
        $providers = array_values(array_intersect($providers, $allowed));

        return empty($providers) ? null : $providers;
    }

    private function hasSearchFilters(Request $request): bool
    {
        $keys = [
            'min_price',
            'max_price',
            'rating',
            'rating_range_min',
            'rating_range_max',
            'property_type',
            'hotel_name',
            'board_type',
            'supplier',
            'sort_by',
        ];

        foreach ($keys as $key) {
            if ($request->filled($key)) {
                return true;
            }
        }

        return false;
    }

    private function getEnabledProviderLabels(): array
    {
        $labelMap = [
            'yalago' => 'Yalago',
            'tbo' => 'TBO',
            'tripindeal' => 'TripInDeal',
        ];

        if (empty($this->enabledHotelProviders)) {
            return array_values($labelMap);
        }

        $labels = [];
        foreach ($this->enabledHotelProviders as $provider) {
            $key = strtolower((string) $provider);
            if (isset($labelMap[$key])) {
                $labels[] = $labelMap[$key];
            }
        }

        return $labels;
    }


    private function formatHotel(Hotel $hotel, ?float $price = null, ?array $boards = null): array
    {
        // decode images if they are JSON string
        $images = is_string($hotel->images)
            ? json_decode($hotel->images, true)
            : $hotel->images;

        return [
            'id'          => $hotel->id,
            'yalago_id'   => $hotel->yalago_id,
            'name'        => $hotel->name,
            'address'     => $hotel->address,
            'rating'      => $hotel->rating,
            'description'      => $hotel->description,
            'rating_text' => $hotel->rating_text,
            'province'    => $hotel->province?->name,
            'location'    => $hotel->location?->name,
            'images'      => $images,
            'image'    => $images[0]['Url'] ?? null,

            // API-related fields
            'price'       => $price
                ? calculatePriceWithCommission($price, $this->hotelCommissionPercentage)
                : null,
            'boards'      => collect($boards ?? [])->pluck('Description')->unique()->values(),
        ];
    }


    public function details(Request $request, int $id)
    {
        if (!$request->has(['check_in', 'check_out', 'room_count'])) {
            return redirect()->route('user.hotels.index')
                ->with('notify_error', 'Missing required parameters.');
        }

        // 1. Fetch hotel with relations
        $hotel = Hotel::with(['province', 'location', 'country'])->findOrFail($id);

        // 2. Parse check-in/out dates
        $startDate = Carbon::parse($request->check_in)->format('Y-m-d');
        $endDate   = Carbon::parse($request->check_out)->format('Y-m-d');

        // 3. Build rooms array dynamically
        $rooms = $this->buildRoomsArray($request);

        // 4. Fetch availability from Yalago API
        $availabilityPayload = [
            "CheckInDate"      => $startDate,
            "CheckOutDate"     => $endDate,
            "EstablishmentIds" => [$hotel->yalago_id],
            "Rooms"            => $rooms,
            "Culture"          => "en-GB",
            "GetPackagePrice"  => false,
            "IsPackage"        => false,
            "GetTaxBreakdown"  => true,
            "GetLocalCharges"  => true,
            "IsBindingPrice"   => true,
        ];

        $availability = Http::withHeaders([
            'x-api-key' => '93082895-c45f-489f-ae10-bed9eaae161e',
            'Accept'    => 'application/json',
        ])->post('https://api.yalago.com/hotels/availability/get', $availabilityPayload)
            ->json('Establishments', []);

        $availableList = collect($availability);

        if ($availableList->isEmpty()) {
            return redirect()->route('user.hotels.search')
                ->with('notify_error', 'No Hotel Found! Please try again.');
        }

        // 5. Fetch detailed hotel info for first room as default
        $firstRoom = $availableList[0]['Rooms'][0] ?? null;
        $hotelApiData = $availableList[0] ?? null;
        $boardsCollection = collect($hotelApiData['Rooms'] ?? [])
            ->flatMap(fn($room) => $room['Boards'] ?? []);

        $cheapestBoard = $boardsCollection->sortBy('NetCost.Amount')->first();


        $hotelFormatted = $this->formatHotel(
            $hotel,
            $cheapestBoard['NetCost']['Amount'] ?? null,
            $boardsCollection->all()
        );
        $roomCode  = $firstRoom['Code'] ?? null;
        $boardCode = $firstRoom['Boards'][0]['Code'] ?? null;

        if (!$roomCode || !$boardCode) {
            return redirect()->back()->with('notify_error', 'Unable to fetch hotel details. Missing room or board codes.');
        }

        $detailPayload = [
            "CheckInDate"     => $startDate,
            "CheckOutDate"    => $endDate,
            "EstablishmentId" => $hotel->yalago_id, // single int, not array
            "Rooms"           => [
                [
                    "Adults"    => $rooms[0]['Adults'] ?? 1,
                    "ChildAges" => $rooms[0]['ChildAges'] ?? [],
                    "RoomCode"  => $roomCode,
                    "BoardCode" => $boardCode,
                ]
            ],
            "Culture"         => "en-GB",
            "GetPackagePrice" => false,
            "GetTaxBreakdown" => true,
            "GetLocalCharges" => true,
            "GetBoardBasis"   => true,
            "CurrencyCode"    => "AED",
        ];
        $response = Http::withHeaders([
            'x-api-key' => '93082895-c45f-489f-ae10-bed9eaae161e',
            'Accept'    => 'application/json',
        ])->post('https://api.yalago.com/hotels/details/get', $detailPayload)
            ->json();
        // 6. Build structured data for view
        $data = [
            'hotel'      => $hotelFormatted,
            'info_items' => $response['InfoItems'] ?? [],
            'api_availability' => $availableList,
            'total_rooms'      => count($rooms),
            'show_extras'      => $hotel->country?->iso_code === 'MV',
            'check_in'         => $startDate,
            'check_out'        => $endDate,
            'rooms_request'    => $rooms,
            'hotelCommissionPercentage'    => $this->hotelCommissionPercentage,
            'provider' => 'yalago',
        ];
        return view('user.hotels.details', $data);
    }

    public function detailsTbo(Request $request, string $code)
    {
        if (!$request->has(['check_in', 'check_out', 'room_count'])) {
            return redirect()->route('user.hotels.index')
                ->with('notify_error', 'Missing required parameters.');
        }

        $provider = new TboHotelProvider();
        $details = $provider->fetchDetails($code);

        if (!$details) {
            return redirect()->route('user.hotels.search')
                ->with('notify_error', 'No Hotel Found! Please try again.');
        }

        $images = collect($details['Images'] ?? [])
            ->map(fn($url) => ['Url' => $url])
            ->values()
            ->all();

        if (empty($images) && !empty($details['Image'])) {
            $images = [['Url' => $details['Image']]];
        }

        $rating = $details['HotelRating'] ?? null;
        $ratingText = null;
        if ($rating !== null) {
            $ratingText = match (true) {
                $rating >= 4.5 => 'Spectacular',
                $rating >= 4.0 => 'Excellent',
                $rating >= 3.5 => 'Good',
                $rating >= 3.0 => 'Above Average',
                $rating >= 2.0 => 'Average',
                $rating >= 1.0 => 'Poor',
                default        => 'Very Poor',
            };
        }

        $tboRates = session('tbo_search_rates', []);
        $tboRate = is_array($tboRates) ? ($tboRates[$code] ?? null) : null;
        $fallbackPrice = $request->query('tbo_price');

        $tboPrice = $tboRate['price'] ?? ($fallbackPrice !== null ? (float) $fallbackPrice : null);
        $tboBookingCode = $tboRate['booking_code'] ?? $request->query('tbo_booking_code');
        $tboRoomNames = $tboRate['room_names'] ?? [];
        $tboTotalFareRaw = $tboRate['total_fare_raw'] ?? $request->query('tbo_total_fare_raw');
        $tboCurrency = $tboRate['currency'] ?? $request->query('tbo_currency');
        $tboMealType = $tboRate['meal_type'] ?? $request->query('tbo_meal_type');

        $hotelFormatted = [
            'id' => null,
            'name' => $details['HotelName'] ?? '',
            'address' => $details['Address'] ?? '',
            'rating' => $rating,
            'rating_text' => $ratingText,
            'description' => $details['Description'] ?? '',
            'images' => $images,
            'image' => $images[0]['Url'] ?? null,
            'price' => $tboPrice,
        ];

        $infoItems = [];
        foreach (($details['HotelFacilities'] ?? []) as $facility) {
            $infoItems[] = ['Description' => $facility];
        }
        foreach (($details['Attractions'] ?? []) as $attraction) {
            $infoItems[] = ['Description' => $attraction];
        }

        $data = [
            'hotel' => $hotelFormatted,
            'info_items' => $infoItems,
            'api_availability' => [],
            'total_rooms' => 0,
            'show_extras' => false,
            'check_in' => $request->query('check_in'),
            'check_out' => $request->query('check_out'),
            'rooms_request' => $this->buildRoomsArray($request),
            'hotelCommissionPercentage' => $this->hotelCommissionPercentage,
            'provider' => 'tbo',
            'tbo_booking_code' => $tboBookingCode,
            'tbo_hotel_code' => $code,
            'tbo_room_names' => $tboRoomNames,
            'tbo_total_fare_raw' => $tboTotalFareRaw,
            'tbo_currency' => $tboCurrency,
            'tbo_meal_type' => $tboMealType,
        ];

        return view('user.hotels.details', $data);
    }

    public function detailsTripInDeal(Request $request, string $code)
    {
        $provider = new TripInDealHotelProvider();
        $details = $provider->fetchDetails($code);

        if (!$details) {
            return redirect()->route('user.hotels.search')
                ->with('notify_error', 'No Hotel Found! Please try again.');
        }

        $images = collect($details['Images'] ?? [])
            ->map(fn($url) => ['Url' => $url])
            ->values()
            ->all();

        $rating = $details['StarRating'] ?? null;
        $ratingText = null;
        if ($rating !== null) {
            $ratingText = match (true) {
                $rating >= 4.5 => 'Spectacular',
                $rating >= 4.0 => 'Excellent',
                $rating >= 3.5 => 'Good',
                $rating >= 3.0 => 'Above Average',
                $rating >= 2.0 => 'Average',
                $rating >= 1.0 => 'Poor',
                default        => 'Very Poor',
            };
        }

        $hotelFormatted = [
            'id' => null,
            'name' => $details['PropertyName'] ?? '',
            'address' => $details['Address'] ?? '',
            'rating' => $rating,
            'rating_text' => $ratingText,
            'description' => $details['Description'] ?? '',
            'images' => $images,
            'image' => $images[0]['Url'] ?? null,
            'price' => null,
        ];

        $data = [
            'hotel' => $hotelFormatted,
            'info_items' => [],
            'api_availability' => [],
            'total_rooms' => 0,
            'show_extras' => false,
            'check_in' => null,
            'check_out' => null,
            'rooms_request' => [],
            'hotelCommissionPercentage' => $this->hotelCommissionPercentage,
            'provider' => 'tripindeal',
        ];

        return view('user.hotels.details', $data);
    }

    public function prepareRoomsData(array $requestData)
    {
        $roomsCount = (int) ($requestData['room_count'] ?? 0);
        $roomsData = [];

        for ($i = 1; $i <= $roomsCount; $i++) {
            $adults = (int) ($requestData["room_{$i}_adults"] ?? 1);
            $childrenCount = (int) ($requestData["room_{$i}_children"] ?? 0);

            // Gather child ages
            $childAges = [];
            for ($c = 1; $c <= $childrenCount; $c++) {
                $age = $requestData["room_{$i}_child_age_{$c}"] ?? null;
                if ($age !== null) {
                    $childAges[] = (int) $age;
                }
            }

            $roomsData[] = [
                'Adults' => $adults,
                'ChildAges' => $childAges,
                'RoomCode' => $requestData["room_{$i}_code"] ?? '',
                'BoardCode' => $requestData["room_{$i}_board_code"] ?? '',
            ];
        }

        return $roomsData;
    }

    public function checkout(Request $request, int $id)
    {
        $requiredParams = ['check_in', 'check_out', 'room_count', 'selected_rooms', 'show_extras'];
        foreach ($requiredParams as $param) {
            if (!$request->has($param)) {
                return redirect()->route('user.hotels.index')
                    ->with('notify_error', 'Missing required information. Please try again.');
            }
        }

        $selectedRoomsCount = (int) $request->query('selected_rooms');
        if ($selectedRoomsCount < 1) {
            return redirect()->route('user.hotels.index')
                ->with('notify_error', 'Please select at least one room.');
        }

        $checkInDate  = Carbon::parse($request['check_in'])->format('Y-m-d');
        $checkOutDate = Carbon::parse($request['check_out'])->format('Y-m-d');

        $roomsData = $this->prepareRoomsData($request->all());

        $hotel = Hotel::with(['province', 'location', 'country'])->findOrFail($id);

        $availabilityPayload = [
            'CheckInDate'      => $checkInDate,
            'CheckOutDate'     => $checkOutDate,
            'EstablishmentIds' => [$hotel->yalago_id],
            'Rooms'            => $roomsData,
            'Culture'          => 'en-GB',
            'GetPackagePrice'  => false,
            'IsPackage'        => false,
            'GetTaxBreakdown'  => true,
            'GetLocalCharges'  => true,
            'IsBindingPrice'   => true,
        ];

        $availability = Http::withHeaders([
            'x-api-key' => '93082895-c45f-489f-ae10-bed9eaae161e',
            'Accept'    => 'application/json',
        ])->post('https://api.yalago.com/hotels/availability/get', $availabilityPayload)
            ->json('Establishments', []);

        if (empty($availability)) {
            return redirect()->route('user.hotels.search')
                ->with('notify_error', 'No availability found.');
        }

        $availableList = collect($availability);

        $selectedRoomsData = [];
        for ($i = 1; $i <= $selectedRoomsCount; $i++) {
            $selectedRoomsData[] = [
                'room_code'   => $request->query("room_{$i}_code"),
                'board_code'  => $request->query("room_{$i}_board_code"),
                'board_title' => $request->query("room_{$i}_board_title"),
                'price'       => (float) $request->query("room_{$i}_price"),
                'room_name'   => $request->query("room_{$i}_name"),
            ];
        }

        $showExtras = filter_var($request->query('show_extras'), FILTER_VALIDATE_BOOLEAN);

        $detailsForRooms = [];
        $allExtras       = collect();
        $netCostDetail   = 0;

        foreach ($selectedRoomsData as $index => &$selectedRoom) {
            $detailPayload = [
                'CheckInDate'     => $checkInDate,
                'CheckOutDate'    => $checkOutDate,
                'EstablishmentId' => $hotel->yalago_id,
                'Rooms' => [[
                    'Adults'    => $roomsData[$index]['Adults'] ?? 1,
                    'ChildAges' => $roomsData[$index]['ChildAges'] ?? [],
                    'RoomCode'  => $selectedRoom['room_code'],
                    'BoardCode' => $selectedRoom['board_code'],
                ]],
                'Culture'         => 'en-GB',
                'GetTaxBreakdown' => true,
                'GetLocalCharges' => true,
                'GetBoardBasis'   => true,
                'CurrencyCode'    => 'AED',
            ];

            $response = Http::withHeaders([
                'x-api-key' => '93082895-c45f-489f-ae10-bed9eaae161e',
                'Accept'    => 'application/json',
            ])->post('https://api.yalago.com/hotels/details/get', $detailPayload)
                ->json();

            $detailsForRooms[] = $response;

            $board = $response['Establishment']['Rooms'][0]['Boards'][0] ?? null;
            if (!$board) {
                abort(400, 'Invalid pricing from supplier.');
            }

            $finalRoomPrice = yalagoFinalPrice(
                $board,
                $this->hotelCommissionPercentage
            );

            $selectedRoom['price'] = $finalRoomPrice;
            $netCostDetail += $finalRoomPrice;

            $extras = collect($response['Establishment']['Rooms'] ?? [])
                ->flatMap(
                    fn($room) =>
                    collect($room['Boards'] ?? [])
                        ->flatMap(
                            fn($b) =>
                            collect($b['Extras'] ?? [])
                                ->map(fn($extra) => [
                                    'room_index' => $index + 1,
                                    'room'       => $room,
                                    'board'      => $b,
                                    'extra'      => $extra,
                                ])
                        )
                );

            $allExtras = $allExtras->merge($extras);
        }

        unset($selectedRoom);

        $netCostDetail  = round($netCostDetail, 2);
        $frontendTotal  = round(collect($selectedRoomsData)->sum('price'), 2);

        $priceChanged = bccomp(
            $frontendTotal,
            round(collect($request->only(
                collect($selectedRoomsData)->keys()->map(fn($i) => "room_" . ($i + 1) . "_price")->toArray()
            ))->sum(), 2),
            2
        ) !== 0;

        $totalPrice = $frontendTotal;

        $boardsCollection = collect($availableList[0]['Rooms'] ?? [])
            ->flatMap(fn($room) => $room['Boards'] ?? []);

        $cheapestBoard = $boardsCollection->sortBy('NetCost.Amount')->first();

        $hotelFormatted = $this->formatHotel(
            $hotel,
            $cheapestBoard['NetCost']['Amount'] ?? null,
            $boardsCollection->all()
        );

        return view('user.hotels.checkout', [
            'hotel'                  => $hotelFormatted,
            'api_availability'       => $availableList,
            'selected_rooms'         => $selectedRoomsData,
            'selected_rooms_details' => $detailsForRooms,
            'rooms_request'          => $roomsData,
            'show_extras'            => $showExtras,
            'yalago_extras'          => $allExtras->values(),
            'total_rooms'            => count($roomsData),
            'total_price'            => $totalPrice,
            'price_changed'          => $priceChanged,
            'check_in'               => $checkInDate,
            'check_out'              => $checkOutDate,
            'hotelCommissionPercentage'              => $this->hotelCommissionPercentage,
            'walletBalance'          => (float) Auth::user()->main_balance,
            'provider'               => 'yalago',
        ]);
    }

    public function checkoutTbo(Request $request, string $code)
    {
        if (!$request->has(['check_in', 'check_out', 'room_count'])) {
            return redirect()->route('user.hotels.index')
                ->with('notify_error', 'Missing required parameters.');
        }

        $tboRates = session('tbo_search_rates', []);
        $tboRate = is_array($tboRates) ? ($tboRates[$code] ?? null) : null;

        $tboBookingCode = $tboRate['booking_code'] ?? $request->query('tbo_booking_code');
        if (empty($tboBookingCode)) {
            return redirect()->route('user.hotels.search')
                ->with('notify_error', 'Pricing information expired. Please search again.');
        }

        $tboPrice = $tboRate['price'] ?? ($request->query('tbo_price') !== null ? (float) $request->query('tbo_price') : null);
        $tboTotalFareRaw = $tboRate['total_fare_raw'] ?? ($request->query('tbo_total_fare_raw') !== null ? (float) $request->query('tbo_total_fare_raw') : null);
        $tboCurrency = $tboRate['currency'] ?? $request->query('tbo_currency') ?? 'AED';
        $tboMealType = $tboRate['meal_type'] ?? $request->query('tbo_meal_type');
        $tboRoomNames = $tboRate['room_names'] ?? [];
        $fallbackRoomName = $tboRate['room_name'] ?? 'Room';

        $roomsData = $this->buildRoomsArray($request);
        $roomCount = max(1, count($roomsData));

        if (empty($tboRoomNames)) {
            $tboRoomNames = array_fill(0, $roomCount, $fallbackRoomName);
        }

        if ($tboPrice === null) {
            return redirect()->route('user.hotels.search')
                ->with('notify_error', 'Pricing information expired. Please search again.');
        }

        if ($tboTotalFareRaw === null && $tboPrice > 0) {
            $tboTotalFareRaw = round($tboPrice / (1 + ($this->hotelCommissionPercentage / 100)), 2);
        }

        $totalPrice = $tboPrice ?? 0;
        $perRoomPrice = $roomCount > 0 ? round($totalPrice / $roomCount, 2) : 0;
        $remainingPrice = $totalPrice;

        $totalSupplier = $tboTotalFareRaw ?? 0;
        $perRoomSupplier = $roomCount > 0 ? round($totalSupplier / $roomCount, 2) : 0;
        $remainingSupplier = $totalSupplier;

        $selectedRoomsData = [];
        for ($i = 0; $i < $roomCount; $i++) {
            $roomPrice = ($i === $roomCount - 1) ? $remainingPrice : $perRoomPrice;
            $remainingPrice = round($remainingPrice - $roomPrice, 2);

            $supplierPrice = ($i === $roomCount - 1) ? $remainingSupplier : $perRoomSupplier;
            $remainingSupplier = round($remainingSupplier - $supplierPrice, 2);

            $selectedRoomsData[] = [
                'room_code' => $tboBookingCode,
                'board_code' => $tboMealType ?? '',
                'board_title' => $tboMealType ? str_replace('_', ' ', $tboMealType) : 'Room Only',
                'price' => $roomPrice,
                'room_name' => $tboRoomNames[$i] ?? $fallbackRoomName,
                'booking_code' => $tboBookingCode,
                'supplier_total_fare' => $supplierPrice,
            ];
        }

        $provider = new TboHotelProvider();
        $details = $provider->fetchDetails($code);

        if (!$details) {
            return redirect()->route('user.hotels.search')
                ->with('notify_error', 'No Hotel Found! Please try again.');
        }

        $images = collect($details['Images'] ?? [])
            ->map(fn($url) => ['Url' => $url])
            ->values()
            ->all();

        if (empty($images) && !empty($details['Image'])) {
            $images = [['Url' => $details['Image']]];
        }

        $hotelFormatted = [
            'id' => null,
            'provider_id' => $code,
            'name' => $details['HotelName'] ?? '',
            'address' => $details['Address'] ?? '',
            'rating' => $details['HotelRating'] ?? null,
            'rating_text' => null,
            'description' => $details['Description'] ?? '',
            'images' => $images,
            'image' => $images[0]['Url'] ?? null,
            'price' => $tboPrice,
        ];

        return view('user.hotels.checkout', [
            'hotel'                  => $hotelFormatted,
            'api_availability'       => [],
            'selected_rooms'         => $selectedRoomsData,
            'selected_rooms_details' => [],
            'rooms_request'          => $roomsData,
            'show_extras'            => false,
            'yalago_extras'          => collect(),
            'total_rooms'            => count($roomsData),
            'total_price'            => $totalPrice,
            'price_changed'          => false,
            'check_in'               => Carbon::parse($request->check_in)->format('Y-m-d'),
            'check_out'              => Carbon::parse($request->check_out)->format('Y-m-d'),
            'hotelCommissionPercentage' => $this->hotelCommissionPercentage,
            'walletBalance'          => (float) Auth::user()->main_balance,
            'provider'               => 'tbo',
            'tbo_currency'           => $tboCurrency,
        ]);
    }

    public function processPayment(Request $request)
    {
        try {
            $supplier = strtolower((string) $request->input('supplier', 'yalago'));

            if ($supplier === 'tbo') {
                $validated = $request->validate([
                    'supplier' => 'required|in:tbo',
                    'hotel_id' => 'required|string',
                    'hotel_name' => 'required|string',
                    'hotel_address' => 'nullable|string',
                    'currency' => 'nullable|string',
                    'check_in' => 'required|date',
                    'check_out' => 'required|date|after:check_in',

                    'rooms' => 'required|array|min:1',
                    'rooms.*.adults' => 'required|integer|min:1',
                    'rooms.*.child_ages' => 'nullable|string',

                    'selected_rooms' => 'required|array|min:1',
                    'selected_rooms.*.room_code' => 'required|string',
                    'selected_rooms.*.board_code' => 'nullable|string',
                    'selected_rooms.*.board_title' => 'nullable|string',
                    'selected_rooms.*.price' => 'required|numeric',
                    'selected_rooms.*.room_name' => 'required|string',
                    'selected_rooms.*.booking_code' => 'required|string',
                    'selected_rooms.*.supplier_total_fare' => 'nullable|numeric',

                    'booking.lead_guest.title' => 'required|string',
                    'booking.lead_guest.first_name' => 'required|string',
                    'booking.lead_guest.last_name' => 'required|string',
                    'booking.lead_guest.email' => 'required|email',
                    'booking.lead_guest.phone' => 'required|string',
                    'booking.lead_guest.address' => 'required|string',

                    'booking.guests' => 'nullable|array',

                    'payment_method' => 'required_without:use_wallet|in:payby,tabby,tamara',
                    'use_wallet' => 'nullable|in:1',
                    'wallet_amount' => 'nullable|numeric|min:0',
                ]);
            } else {
                $validated = $request->validate([
                    'supplier' => 'nullable|string',
                    'hotel_id' => 'required|integer',
                    'check_in' => 'required|date',
                    'check_out' => 'required|date|after:check_in',

                    'rooms' => 'required|array|min:1',
                    'rooms.*.adults' => 'required|integer|min:1',
                    'rooms.*.child_ages' => 'nullable|string',

                    'selected_rooms' => 'required|array|min:1',
                    'selected_rooms.*.room_code' => 'required|string',
                    'selected_rooms.*.board_code' => 'required|string',
                    'selected_rooms.*.board_title' => 'required|string',
                    'selected_rooms.*.price' => 'required|numeric',
                    'selected_rooms.*.room_name' => 'required|string',

                    'booking.lead_guest.title' => 'required|string',
                    'booking.lead_guest.first_name' => 'required|string',
                    'booking.lead_guest.last_name' => 'required|string',
                    'booking.lead_guest.email' => 'required|email',
                    'booking.lead_guest.phone' => 'required|string',
                    'booking.lead_guest.address' => 'required|string',

                    'booking.guests' => 'nullable|array',
                    'booking.extras' => 'nullable|array',

                    'payment_method' => 'required_without:use_wallet|in:payby,tabby,tamara',
                    'use_wallet' => 'nullable|in:1',
                    'wallet_amount' => 'nullable|numeric|min:0',
                    'flight_details' => 'nullable|array',
                ]);
            }

            $supplier = strtolower((string) ($validated['supplier'] ?? 'yalago'));

            if ($supplier !== 'tbo') {
                // Get hotel information from POST data
                $hotelId = $validated['hotel_id'];
                $hotel = Hotel::where('yalago_id', $hotelId)->first();

                if (!$hotel) {
                    return redirect()->route('user.hotels.index')
                        ->with('notify_error', 'Hotel not found.');
                }

                if (count($validated['rooms']) !== count($validated['selected_rooms'])) {
                    return back()->with('notify_error', 'Room selection mismatch.');
                }
            } else {
                $hotel = null;
                if (count($validated['rooms']) !== count($validated['selected_rooms'])) {
                    return back()->with('notify_error', 'Room selection mismatch.');
                }
            }


            // Build rooms data from POST data
            $roomsData = [];

            foreach ($validated['rooms'] as $room) {
                $childAges = [];

                if (!empty($room['child_ages'])) {
                    $childAges = array_map('intval', explode(',', $room['child_ages']));
                }

                $roomsData[] = [
                    'Adults' => (int) $room['adults'],
                    'ChildAges' => $childAges,
                ];
            }

            // Calculate extras total
            $extrasTotal = 0;
            $extrasData = [];

            if (!empty($validated['booking']['extras'] ?? null)) {
                foreach ($validated['booking']['extras'] as $extra) {
                    $price = (float) ($extra['price'] ?? 0);

                    $extrasTotal += $price;

                    $extrasData[] = [
                        'title' => $extra['title'] ?? '',
                        'price' => $price,
                        'option_id' => $extra['option_id'] ?? null,
                        'extra_id' => $extra['extra_id'] ?? null,
                        'extra_type_id' => $extra['extra_type_id'] ?? null,
                    ];
                }
            }

            // Calculate total amount
            $roomsTotal = collect($validated['selected_rooms'])
                ->sum(fn($room) => (float) $room['price']);

            $totalAmount = $roomsTotal + $extrasTotal;

            // Get source market from IP
            $sourceMarket = $this->getSourceMarketFromIP();

            // Prepare booking data
            $bookingData = [
                'yalago_hotel_id' => $supplier === 'tbo' ? $validated['hotel_id'] : $hotel->yalago_id,
                'hotel_name' => $supplier === 'tbo' ? $validated['hotel_name'] : $hotel->name,
                'hotel_address' => $supplier === 'tbo' ? ($validated['hotel_address'] ?? null) : $hotel->address,

                'check_in_date' => $validated['check_in'],
                'check_out_date' => $validated['check_out'],

                'rooms_data' => $roomsData,
                'selected_rooms' => $validated['selected_rooms'],

                'lead_guest' => $validated['booking']['lead_guest'],
                'guests' => $validated['booking']['guests'] ?? null,

                'extras' => $extrasData,
                'extras_total' => $extrasTotal,

                'rooms_total' => $roomsTotal,
                'total_amount' => $totalAmount,

                'payment_method' => $validated['payment_method'],
                'flight_details' => $validated['flight_details'] ?? null,
                'source_market' => $this->getSourceMarketFromIP(),
                'supplier' => $supplier,
                'currency' => $supplier === 'tbo' ? ($validated['currency'] ?? 'AED') : 'AED',
            ];



            // Initialize HotelService
            $hotelService = new \App\Services\HotelService();

            // Create booking record
            $booking = $hotelService->createBookingRecord($bookingData);


            if ($supplier !== 'tbo') {
                // Verify availability
                $availabilityCheck = $hotelService->verifyAvailability($booking);

                if (!$availabilityCheck['success']) {
                    $booking->update([
                        'booking_status' => 'failed',
                        'payment_status' => 'failed',
                    ]);

                    return redirect()->route('user.hotels.index')
                        ->with('notify_error', $availabilityCheck['error']);
                }
            }

            // Store wallet intent on booking (do NOT deduct yet — only on verified success)
            $useWallet = !empty($validated['use_wallet']);
            $walletDeduction = 0;

            if ($useWallet) {
                $user = Auth::user();
                $requestedWalletAmount = (float) ($validated['wallet_amount'] ?? 0);
                $walletDeduction = min($requestedWalletAmount, (float) $user->main_balance, $totalAmount);

                if ($walletDeduction > 0) {
                    $booking->update([
                        'wallet_amount' => $walletDeduction,
                    ]);
                }
            }

            $remainingAmount = $totalAmount - $walletDeduction;

            // If wallet covers the full amount, redirect to success for processing
            if ($remainingAmount <= 0) {
                $booking->update([
                    'payment_method' => 'wallet',
                ]);

                return redirect()->route('user.hotels.payment.success', ['booking' => $booking->id]);
            }

            // Get payment redirect URL for remaining amount
            try {
                $redirectUrl = $hotelService->getRedirectUrl($booking, $validated['payment_method']);
                return redirect($redirectUrl);
            } catch (\Exception $e) {
                $booking->update([
                    'booking_status' => 'failed',
                    'payment_status' => 'failed',
                    'wallet_amount' => 0,
                ]);

                Log::error('Payment redirect failed', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage()
                ]);

                return redirect()->route('user.hotels.payment.failed', ['booking' => $booking->id])
                    ->with('notify_error', 'Unable to process payment. Please try again.');
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('notify_error', 'Please fill in all required fields correctly.');
        } catch (\Exception $e) {
            Log::error('Hotel booking process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('user.hotels.index')
                ->with('notify_error', 'An error occurred while processing your booking. Please try again.');
        }
    }

    public function paymentSuccess(Request $request, $booking)
    {
        try {
            $booking = B2bHotelBooking::findOrFail($booking);

            // Prevent re-processing if already paid
            if ($booking->isPaid() && $booking->isConfirmed()) {
                return redirect()->route('user.hotels.payment.success.view', ['booking' => $booking->id]);
            }

            $hotelService = new \App\Services\HotelService();

            // Verify payment based on payment method
            if ($booking->payment_method === 'wallet' && $booking->wallet_amount >= $booking->total_amount) {
                // Full wallet payment — verify balance is still sufficient
                $vendor = $booking->vendor;
                if ((float) $vendor->main_balance < (float) $booking->wallet_amount) {
                    $booking->update([
                        'payment_status' => 'failed',
                        'booking_status' => 'failed',
                        'wallet_amount' => 0,
                    ]);
                    return redirect()->route('user.hotels.payment.failed', ['booking' => $booking->id])
                        ->with('notify_error', 'Insufficient wallet balance.');
                }
                $verificationResult = ['success' => true, 'data' => ['method' => 'wallet']];
            } elseif ($booking->payment_method === 'payby') {
                $verificationResult = $hotelService->verifyPayByPayment($booking);
            } elseif ($booking->payment_method === 'tabby') {
                $verificationResult = $hotelService->verifyTabbyPayment($booking);
            } elseif ($booking->payment_method === 'tamara') {
                $verificationResult = $hotelService->verifyTamaraPayment($booking);
            } else {
                throw new \Exception('Invalid payment method');
            }

            if (!$verificationResult['success']) {
                $booking->update([
                    'payment_status' => 'failed',
                    'booking_status' => 'failed',
                    'wallet_amount' => 0,
                ]);

                $hotelService->sendBookingFailureEmail($booking, $verificationResult['error'] ?? 'Payment verification failed');
                $hotelService->sendBookingFailureEmailToAdmin($booking, $verificationResult['error'] ?? 'Payment verification failed');

                return redirect()->route('user.hotels.payment.failed', ['booking' => $booking->id])
                    ->with('notify_error', 'Payment verification failed. Please contact support.');
            }

            // Payment verified — now deduct wallet if used
            if ($booking->wallet_amount > 0) {
                B2bWalletLedger::recordDebit(
                    $booking->b2b_vendor_id,
                    (float) $booking->wallet_amount,
                    'Hotel Booking #' . $booking->booking_number,
                    B2bHotelBooking::class,
                    $booking->id
                );
            }

            // Update payment status
            $booking->update([
                'payment_status' => 'paid',
                'payment_response' => $verificationResult['data'] ?? null,
            ]);

            // Place booking order with Yalago
            $bookingResult = $hotelService->placeBookingOrder($booking);

            if (!$bookingResult['success']) {
                $hotelService->sendBookingFailureEmail($booking, $bookingResult['error'] ?? 'Booking placement failed');
                $hotelService->sendBookingFailureEmailToAdmin($booking, $bookingResult['error'] ?? 'Booking placement failed');

                return redirect()->route('user.hotels.payment.failed', ['booking' => $booking->id])
                    ->with('notify_error', 'Unable to confirm your booking. Our team will contact you shortly.');
            }

            // Send confirmation emails
            $hotelService->sendBookingConfirmationEmail($booking);
            $hotelService->sendBookingConfirmationEmailToAdmin($booking);

            return redirect()->route('user.hotels.payment.success.view', ['booking' => $booking->id]);
        } catch (\Exception $e) {
            Log::error('Payment success processing failed', [
                'booking_id' => $booking ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('user.hotels.index')
                ->with('notify_error', 'An error occurred while processing your payment. Please contact support.');
        }
    }

    public function paymentSuccessView($booking)
    {
        try {
            $booking = B2bHotelBooking::findOrFail($booking);

            if (!$booking->isPaid()) {
                return redirect()->route('user.hotels.index')
                    ->with('notify_error', 'Invalid booking access.');
            }

            return view('user.hotels.payment-success', compact('booking'));
        } catch (\Exception $e) {
            return redirect()->route('user.hotels.index')
                ->with('notify_error', 'Booking not found.');
        }
    }

    public function paymentFailed(Request $request, $booking = null)
    {
        try {
            if ($booking) {
                $booking = B2bHotelBooking::findOrFail($booking);

                if ($booking->payment_status === 'pending') {
                    $booking->update([
                        'payment_status' => 'failed',
                        'booking_status' => 'failed',
                        'wallet_amount' => 0,
                    ]);

                    $hotelService = new \App\Services\HotelService();
                    $hotelService->sendBookingFailureEmail($booking, 'Payment was cancelled or failed');
                    $hotelService->sendBookingFailureEmailToAdmin($booking, 'Payment was cancelled or failed');
                }

                return view('user.hotels.payment-failed', compact('booking'));
            }

            return view('user.hotels.payment-failed', ['booking' => null]);
        } catch (\Exception $e) {
            return view('user.hotels.payment-failed', ['booking' => null]);
        }
    }

    protected function getSourceMarketFromIP()
    {
        try {
            $ip = request()->ip();
            $response = file_get_contents("https://ipinfo.io/{$ip}/json");
            $data = json_decode($response, true);

            if (isset($data['country'])) {
                return $data['country'];
            }
        } catch (\Exception $e) {
           //
        }

        return 'AE';
    }
}
