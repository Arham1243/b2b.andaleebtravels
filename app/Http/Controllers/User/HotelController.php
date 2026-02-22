<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Country;
use App\Models\Hotel;
use App\Models\Config;
use App\Models\Province;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class HotelController extends Controller
{
    protected $hotelCommissionPercentage;
    public function __construct()
    {
        $config = Config::pluck('config_value', 'config_key')->toArray();
        $this->hotelCommissionPercentage = $config['HOTEL_COMMISSION_PERCENTAGE'] ?? 10;
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

        // 2. Resolve destination to hotel IDs
        $hotelIds = $this->resolveDestinationToHotelIds($request->destination);

        $hotels = collect();

        if ($hotelIds->isNotEmpty()) {
            // 3. Fetch availability from API
            $hotels = $this->fetchAvailability($hotelIds, $rooms, $request);

            // 4. Apply filters
            $hotels = $this->applyFilters($hotels, $request);

            // 5. Apply sorting
            $hotels = $this->applySorting($hotels, $request);

            $hotels = $this->formatHotels($hotels);
        }
        return view('user.hotels.search', [
            'hotels' => $hotels->values()
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
    protected function resolveDestinationToHotelIds($destination)
    {
        $hotelIds = collect();

        $country = Country::where('name', $destination)->where('status', 'active')->first();
        $province = Province::where('name', $destination)->where('status', 'active')->first();
        $location = Location::where('name', $destination)->where('status', 'active')->first();

        if ($country) {
            $provinceIds = $country->provinces()->pluck('id');
            $locationIds = Location::whereIn('province_id', $provinceIds)->pluck('id');
            $hotelIds = Hotel::whereIn('location_id', $locationIds)->pluck('yalago_id');
        } elseif ($province) {
            $locationIds = $province->locations()->pluck('id');
            $hotelIds = Hotel::whereIn('location_id', $locationIds)->pluck('yalago_id');
        } elseif ($location) {
            $hotelIds = Hotel::where('location_id', $location->id)->pluck('yalago_id');
        } else {
            $hotel = Hotel::where('name', $destination)->where('status', 'active')->first();
            if ($hotel) $hotelIds = collect([$hotel->yalago_id]);
        }

        return $hotelIds;
    }

    // 4. Fetch availability from API
    protected function fetchAvailability($hotelIds, $rooms, Request $request)
{
    $startDate = Carbon::parse($request->check_in)->format('Y-m-d');
    $endDate   = Carbon::parse($request->check_out)->format('Y-m-d');

    $payload = [
        "CheckInDate"      => $startDate,
        "CheckOutDate"     => $endDate,
        "EstablishmentIds" => $hotelIds->toArray(),
        "Rooms"            => $rooms,
        "SourceMarket"     => "",
        "Culture"          => "en-GB",
        "GetPackagePrice"  => false,
        "GetTaxBreakdown"  => true,
        "IsPackage"        => false,
        "GetLocalCharges"  => true,
        "IsBindingPrice"   => true
    ];

    try {
        $response = Http::timeout(30)          
            ->connectTimeout(10)               
            ->retry(2, 2000)                   
            ->withHeaders([
                'x-api-key' => '93082895-c45f-489f-ae10-bed9eaae161e',
                'Accept'    => 'application/json'
            ])
            ->post('https://api.yalago.com/hotels/availability/get', $payload);

        if ($response->failed()) {
            Log::error('Yalago API failed', [
                'status' => $response->status(),
                'body'   => $response->body()
            ]);

            return collect(); // safe fallback
        }

        return collect($response->json()['Establishments'] ?? []);

    } catch (\Exception $e) {

        Log::error('Yalago API timeout/error', [
            'message' => $e->getMessage()
        ]);

        return collect(); // prevent crash
    }
}

    // 5. Apply all filters
    protected function applyFilters($hotels, Request $request)
    {
        // Price range
        if ($request->filled('min_price') || $request->filled('max_price')) {
            $min = $request->min_price;
            $max = $request->max_price;
            $hotels = $hotels->filter(function ($hotel) use ($min, $max) {
                foreach ($hotel['Rooms'] as $room) {
                    foreach ($room['Boards'] as $board) {
                        $price = $board['IsBindingPrice'] ? $board['GrossCost']['Amount'] : $board['NetCost']['Amount'];
                        if ((!$min || $price >= $min) && (!$max || $price <= $max)) return true;
                    }
                }
                return false;
            });
        }

        // Rating exact
        if ($request->filled('rating')) {
            $ratings = explode(',', $request->rating);
            $hotels = $hotels->filter(fn($hotel) => in_array($hotel['EstablishmentInfo']['Rating'], $ratings));
        }

        // Rating range
        $rMin = $request->input('rating_range_min');
        $rMax = $request->input('rating_range_max');
        if ($rMin || $rMax) {
            $hotels = $hotels->filter(
                fn($hotel) => (!$rMin || $hotel['EstablishmentInfo']['Rating'] >= $rMin) &&
                    (!$rMax || $hotel['EstablishmentInfo']['Rating'] <= $rMax)
            );
        }

        // Property type
        if ($request->filled('property_type')) {
            $types = explode(',', $request->property_type);
            $hotels = $hotels->filter(fn($hotel) => in_array($hotel['EstablishmentInfo']['AccomodationType'], $types));
        }

        // Hotel name
        if ($request->filled('hotel_name')) {
            $name = $request->hotel_name;
            $hotels = $hotels->filter(
                fn($hotel) =>
                stripos($hotel['EstablishmentInfo']['EstablishmentName'], $name) !== false
            );
        }

        // Board type
        if ($request->filled('board_type')) {
            $boards = explode(',', $request->board_type);
            $hotels = $hotels->filter(function ($hotel) use ($boards) {
                foreach ($hotel['Rooms'] as $room) {
                    foreach ($room['Boards'] as $board) {
                        if (in_array($board['Description'], $boards)) return true;
                    }
                }
                return false;
            });
        }

        return $hotels;
    }

    // 6. Apply sorting
    protected function applySorting($hotels, Request $request)
    {
        $sort = $request->sort_by;
        if (!$sort) return $hotels;

        if ($sort === 'price_low_to_high') {
            $hotels = $hotels->sortBy(
                fn($hotel) =>
                $hotel['Rooms'][0]['Boards'][0]['IsBindingPrice']
                    ? $hotel['Rooms'][0]['Boards'][0]['GrossCost']['Amount']
                    : $hotel['Rooms'][0]['Boards'][0]['NetCost']['Amount']
            );
        } elseif ($sort === 'price_high_to_low') {
            $hotels = $hotels->sortByDesc(
                fn($hotel) =>
                $hotel['Rooms'][0]['Boards'][0]['IsBindingPrice']
                    ? $hotel['Rooms'][0]['Boards'][0]['GrossCost']['Amount']
                    : $hotel['Rooms'][0]['Boards'][0]['NetCost']['Amount']
            );
        } elseif ($sort === 'recommended') {
            $hotels = $hotels->filter(fn($hotel) => $hotel['EstablishmentInfo']['Rating'] == 5);
        } elseif ($sort === 'top_rated') {
            $hotels = $hotels->filter(fn($hotel) => in_array($hotel['EstablishmentInfo']['Rating'], [5, 4]));
        }

        return $hotels;
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

            $boards = collect($item['Rooms'])
                ->flatMap(fn($room) => $room['Boards']);


            $cheapestBoard = $boards
                ->sortBy('NetCost.Amount')
                ->first();

            // decode images if they are JSON string
            $images = is_string($localHotel->images)
                ? json_decode($localHotel->images, true)
                : $localHotel->images;

            return [
                'id' => $localHotel?->id,

                'name' => $localHotel?->name,
                'address' => $localHotel?->address,
                'rating' => $localHotel?->rating,
                'rating_text' => $localHotel?->rating_text,

                'province' => $localHotel?->province?->name,
                'location' => $localHotel?->location?->name,

                'image'    => $images[0]['Url'] ?? null,

                'price' => calculatePriceWithCommission(data_get($cheapestBoard, 'NetCost.Amount'), $this->hotelCommissionPercentage),

                'boards' => $boards
                    ->pluck('Description')
                    ->unique()
                    ->values(),
            ];
        });
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
        ]);
    }

    public function processPayment(Request $request)
    {
        try {
            $validated = $request->validate([
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

                'payment_method' => 'required|in:payby,tabby,wallet',
                'flight_details' => 'nullable|array',
            ]);

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

            if (!empty($validated['booking']['extras'])) {
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
                'yalago_hotel_id' => $hotel->yalago_id,
                'hotel_name' => $hotel->name,
                'hotel_address' => $hotel->address,

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
                'flight_details' => $validated['flight_details'],
                'source_market' => $this->getSourceMarketFromIP(),
            ];



            // Initialize HotelService
            $hotelService = new \App\Services\HotelService();

            // Create booking record
            $booking = $hotelService->createBookingRecord($bookingData);


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

            // Handle wallet payment
            if ($validated['payment_method'] === 'wallet') {
                $user = Auth::user();
                if ($user->main_balance < $totalAmount) {
                    $booking->update([
                        'booking_status' => 'failed',
                        'payment_status' => 'failed',
                    ]);
                    return redirect()->back()
                        ->with('notify_error', 'Insufficient wallet balance. Your balance is ' . formatPrice($user->main_balance));
                }

                $user->decrement('main_balance', $totalAmount);

                $booking->update([
                    'payment_status' => 'paid',
                    'payment_reference' => 'WALLET-' . strtoupper(uniqid()),
                ]);

                return redirect()->route('user.hotels.payment.success', ['booking' => $booking->id]);
            }

            // Get payment redirect URL
            try {
                $redirectUrl = $hotelService->getRedirectUrl($booking, $validated['payment_method']);
                return redirect($redirectUrl);
            } catch (\Exception $e) {
                $booking->update([
                    'booking_status' => 'failed',
                    'payment_status' => 'failed',
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
            $booking = \App\Models\HotelBooking::findOrFail($booking);

            // Prevent re-processing if already paid
            if ($booking->isPaid() && $booking->isConfirmed()) {
                return redirect()->route('user.hotels.payment.success.view', ['booking' => $booking->id]);
            }

            $hotelService = new \App\Services\HotelService();

            // Verify payment based on payment method
            if ($booking->payment_method === 'wallet') {
                // Wallet payments are already verified at checkout
                $verificationResult = ['success' => true, 'data' => ['method' => 'wallet']];
            } elseif ($booking->payment_method === 'payby') {
                $verificationResult = $hotelService->verifyPayByPayment($booking);
            } elseif ($booking->payment_method === 'tabby') {
                $verificationResult = $hotelService->verifyTabbyPayment($booking);
            } else {
                throw new \Exception('Invalid payment method');
            }

            if (!$verificationResult['success']) {
                $booking->update([
                    'payment_status' => 'failed',
                    'booking_status' => 'failed',
                ]);

                $hotelService->sendBookingFailureEmail($booking, $verificationResult['error'] ?? 'Payment verification failed');
                $hotelService->sendBookingFailureEmailToAdmin($booking, $verificationResult['error'] ?? 'Payment verification failed');

                return redirect()->route('user.hotels.payment.failed', ['booking' => $booking->id])
                    ->with('notify_error', 'Payment verification failed. Please contact support.');
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
            $booking = \App\Models\HotelBooking::findOrFail($booking);

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
                $booking = \App\Models\HotelBooking::findOrFail($booking);

                if ($booking->payment_status === 'pending') {
                    $booking->update([
                        'payment_status' => 'failed',
                        'booking_status' => 'failed',
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
            Log::warning('Failed to get source market from IP', ['error' => $e->getMessage()]);
        }

        return 'AE';
    }
}
