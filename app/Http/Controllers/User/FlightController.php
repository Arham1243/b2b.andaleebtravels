<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Config;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class FlightController extends Controller
{
    private string $sabreBasicAuth = 'VmpFNk1qVTROak13T2poT1NrdzZRVUU9OlJtRnBjMkZzTVRBPQ==';
    private ?array $enabledFlightProviders = null;

    public function __construct()
    {
        $config = Config::pluck('config_value', 'config_key')->toArray();
        $adminProviders = $this->parseProviderConfig($config['FLIGHT_SEARCH_PROVIDERS'] ?? null);

        $user = Auth::user();
        $userProviders = $this->parseProviderConfig($user?->flight_search_providers ?? null);

        $this->enabledFlightProviders = $userProviders ?? $adminProviders ?? ['sabre'];
    }

    private function sabreHttp(): PendingRequest
    {
        return Http::timeout((int) config('services.sabre.http_timeout', 90))
            ->connectTimeout((int) config('services.sabre.http_connect_timeout', 30));
    }

    public function index()
    {
        return view('user.flights.index');
    }

    public function search(Request $request)
    {
        if (!$this->isProviderEnabled('sabre')) {
            return view('user.flights.search', [
                'results' => [],
                'messages' => [['severity' => 'Error', 'text' => 'Sabre is disabled for your account.']],
                'itineraryCount' => 0,
                'tripType' => $request->input('trip_type', 'one_way'),
                'priceRange' => ['min' => 0.0, 'max' => 1.0],
                'filterCatalog' => $this->buildFilterCatalog([]),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'trip_type' => 'required|string|in:one_way,round_trip,multi_city',
            'from' => 'nullable|string|size:3',
            'to' => 'nullable|string|size:3',
            'departure_date' => 'nullable|string',
            'return_date' => 'nullable|string',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'infants' => 'nullable|integer|min:0',
            'direct_flight' => 'nullable',
            'nearby_airports' => 'nullable',
            'student_fare' => 'nullable',
            'segments' => 'nullable|array|min:2|max:5',
            'segments.*.from' => 'nullable|string|size:3',
            'segments.*.to' => 'nullable|string|size:3',
            'segments.*.departure_date' => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($request) {
            $tripType = $request->input('trip_type', 'one_way');

            if ((int) $request->input('infants', 0) > (int) $request->input('adults', 1)) {
                $validator->errors()->add('infants', 'Infants cannot exceed the number of adults.');
            }

            if ($tripType === 'multi_city') {
                $segments = $request->input('segments', []);

                if (!is_array($segments) || count($segments) < 2) {
                    $validator->errors()->add('segments', 'At least two flight segments are required for a multi-city search.');
                    return;
                }

                foreach ($segments as $index => $segment) {
                    $from = strtoupper(trim((string) ($segment['from'] ?? '')));
                    $to = strtoupper(trim((string) ($segment['to'] ?? '')));
                    $departureDate = trim((string) ($segment['departure_date'] ?? ''));
                    $segmentLabel = 'Segment ' . ($index + 1);

                    if ($from === '') {
                        $validator->errors()->add("segments.$index.from", "{$segmentLabel} origin is required.");
                    }

                    if ($to === '') {
                        $validator->errors()->add("segments.$index.to", "{$segmentLabel} destination is required.");
                    }

                    if ($from !== '' && $to !== '' && $from === $to) {
                        $validator->errors()->add("segments.$index.to", "{$segmentLabel} destination must be different from the origin.");
                    }

                    if ($departureDate === '') {
                        $validator->errors()->add("segments.$index.departure_date", "{$segmentLabel} departure date is required.");
                    }
                }

                return;
            }

            $from = strtoupper(trim((string) $request->input('from', '')));
            $to = strtoupper(trim((string) $request->input('to', '')));
            $departureDate = trim((string) $request->input('departure_date', ''));
            $returnDate = trim((string) $request->input('return_date', ''));

            if ($from === '') {
                $validator->errors()->add('from', 'Origin is required.');
            }

            if ($to === '') {
                $validator->errors()->add('to', 'Destination is required.');
            }

            if ($from !== '' && $to !== '' && $from === $to) {
                $validator->errors()->add('to', 'Destination must be different from the origin.');
            }

            if ($departureDate === '') {
                $validator->errors()->add('departure_date', 'Departure date is required.');
            }

            if ($tripType === 'round_trip' && $returnDate === '') {
                $validator->errors()->add('return_date', 'Return date is required for a round trip.');
            }
        });

        $validated = $validator->validate();
        $searchData = $this->normalizeSearchData($validated);

        try {
            $token = $this->getSabreToken();
        } catch (\Exception $e) {
            return view('user.flights.search', [
                'results' => [],
                'messages' => [['severity' => 'Error', 'text' => $e->getMessage()]],
                'itineraryCount' => 0,
                'tripType' => $searchData['trip_type'] ?? 'one_way',
                'priceRange' => ['min' => 0.0, 'max' => 1.0],
                'filterCatalog' => $this->buildFilterCatalog([]),
            ]);
        }

        $payload = $this->buildSabrePayload($searchData);
        $response = $this->sabreHttp()->withToken($token)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('https://api.cert.platform.sabre.com/v5/offers/shop', $payload);

        if (!$response->successful()) {
            return view('user.flights.search', [
                'results' => [],
                'messages' => [['severity' => 'Error', 'text' => 'Flight search failed.']],
                'itineraryCount' => 0,
                'tripType' => $searchData['trip_type'] ?? 'one_way',
                'priceRange' => ['min' => 0.0, 'max' => 1.0],
                'filterCatalog' => $this->buildFilterCatalog([]),
            ]);
        }

        $data = $response->json();
        $grouped = $data['groupedItineraryResponse'] ?? [];
        $messages = $grouped['messages'] ?? [];
        $itineraryCount = (int) ($grouped['statistics']['itineraryCount'] ?? 0);

        $results = $this->extractItineraries($grouped);
        $resultsById = collect($results)->keyBy('id')->toArray();

        session([
            'flight_search_payload' => $payload,
            'flight_search_response' => $grouped,
            'flight_search_results' => $resultsById,
            'flight_search_params' => $searchData,
        ]);

        return view('user.flights.search', [
            'results' => $results,
            'messages' => $messages,
            'itineraryCount' => $itineraryCount,
            'tripType' => $searchData['trip_type'] ?? 'one_way',
            'priceRange' => $this->buildPriceRange($results),
            'filterCatalog' => $this->buildFilterCatalog($results),
        ]);
    }

    /** @param list<array<string,mixed>> $results */
    private function buildPriceRange(array $results): array
    {
        $nums = [];
        foreach ($results as $r) {
            $v = $r['totalPrice'] ?? null;
            if ($v !== null && is_numeric($v)) {
                $nums[] = (float) $v;
            }
        }
        if (empty($nums)) {
            return ['min' => 0.0, 'max' => 1.0];
        }

        return [
            'min' => (float) min($nums),
            'max' => (float) max($nums),
        ];
    }

    private function normalizeSearchData(array $data): array
    {
        $tripType = $data['trip_type'] ?? 'one_way';
        $normalized = [
            'trip_type' => $tripType,
            'adults' => (int) ($data['adults'] ?? 1),
            'children' => (int) ($data['children'] ?? 0),
            'infants' => (int) ($data['infants'] ?? 0),
            'direct_flight' => filter_var($data['direct_flight'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'nearby_airports' => filter_var($data['nearby_airports'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'student_fare' => filter_var($data['student_fare'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];

        if ($tripType === 'multi_city') {
            $segments = collect($data['segments'] ?? [])
                ->map(function ($segment) {
                    return [
                        'from' => strtoupper(trim((string) ($segment['from'] ?? ''))),
                        'to' => strtoupper(trim((string) ($segment['to'] ?? ''))),
                        'departure_date' => trim((string) ($segment['departure_date'] ?? '')),
                    ];
                })
                ->filter(function ($segment) {
                    return $segment['from'] !== '' && $segment['to'] !== '' && $segment['departure_date'] !== '';
                })
                ->values()
                ->all();

            $firstSegment = $segments[0] ?? [];
            $lastSegment = !empty($segments) ? $segments[count($segments) - 1] : [];

            $normalized['segments'] = $segments;
            $normalized['from'] = $firstSegment['from'] ?? null;
            $normalized['to'] = $lastSegment['to'] ?? null;
            $normalized['departure_date'] = $firstSegment['departure_date'] ?? null;
            $normalized['return_date'] = null;

            return $normalized;
        }

        $normalized['from'] = strtoupper(trim((string) ($data['from'] ?? '')));
        $normalized['to'] = strtoupper(trim((string) ($data['to'] ?? '')));
        $normalized['departure_date'] = trim((string) ($data['departure_date'] ?? ''));
        $normalized['return_date'] = $tripType === 'round_trip'
            ? trim((string) ($data['return_date'] ?? ''))
            : null;

        return $normalized;
    }

    private function getSabreToken(): string
    {
        if (empty($this->sabreBasicAuth)) {
            throw new \Exception('Sabre credentials are not configured.');
        }

        $response = $this->sabreHttp()->asForm()->withHeaders([
            'Authorization' => 'Basic ' . $this->sabreBasicAuth,
        ])->post('https://api.cert.platform.sabre.com/v2/auth/token', [
            'grant_type' => 'client_credentials',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Unable to fetch Sabre token.');
        }

        $token = $response->json('access_token');
        if (!$token) {
            throw new \Exception('Sabre token missing in response.');
        }

        return $token;
    }

    private function buildSabrePayload(array $data): array
    {
        $tripType = $data['trip_type'] ?? 'one_way';
        $origin = strtoupper((string) ($data['from'] ?? ''));
        $destination = strtoupper((string) ($data['to'] ?? ''));

        $adults = (int) $data['adults'];
        $children = (int) ($data['children'] ?? 0);
        $infants = (int) ($data['infants'] ?? 0);

        $passengerTypes = [
            ['Code' => 'ADT', 'Quantity' => $adults],
        ];

        if ($children > 0) {
            $passengerTypes[] = ['Code' => 'C06', 'Quantity' => $children];
        }

        if ($infants > 0) {
            $passengerTypes[] = ['Code' => 'INF', 'Quantity' => $infants];
        }

        if ($infants > 0) {
            $passengerTypes = array_map(function ($entry) {
                $entry['TPA_Extensions'] = [
                    'VoluntaryChanges' => [
                        'Match' => 'All',
                        'Penalty' => [
                            ['Type' => 'Refund'],
                        ],
                    ],
                ];
                return $entry;
            }, $passengerTypes);
        }

        $originDestinations = [];

        if ($tripType === 'multi_city') {
            foreach ($data['segments'] ?? [] as $segment) {
                $originDestinations[] = [
                    'DepartureDateTime' => $this->formatSabreDateTime($segment['departure_date']),
                    'OriginLocation' => ['LocationCode' => strtoupper($segment['from'])],
                    'DestinationLocation' => ['LocationCode' => strtoupper($segment['to'])],
                ];
            }
        } else {
            $departureDate = $this->formatSabreDateTime($data['departure_date']);
            $returnDate = !empty($data['return_date']) ? $this->formatSabreDateTime($data['return_date']) : null;

            $originDestinations[] = [
                'DepartureDateTime' => $departureDate,
                'OriginLocation' => ['LocationCode' => $origin],
                'DestinationLocation' => ['LocationCode' => $destination],
            ];

            if ($tripType === 'round_trip' && $returnDate) {
                $originDestinations[] = [
                    'DepartureDateTime' => $returnDate,
                    'OriginLocation' => ['LocationCode' => $destination],
                    'DestinationLocation' => ['LocationCode' => $origin],
                ];
            }
        }

        $travelPreferences = [
            'MaxStopsQuantity' => !empty($data['direct_flight']) ? 0 : 1,
        ];

        if ($children > 0 && $infants === 0) {
            $travelPreferences['Baggage'] = [
                'RequestType' => 'C',
                'Description' => true,
            ];
        }

        $travelerInfoSummary = [
            'AirTravelerAvail' => [
                [
                    'PassengerTypeQuantity' => $passengerTypes,
                ],
            ],
        ];

        if ($children > 0 && $infants === 0) {
            $travelerInfoSummary['PriceRequestInformation'] = [
                'TPA_Extensions' => new \stdClass(),
            ];
        }

        return [
            'OTA_AirLowFareSearchRQ' => [
                'Version' => '5',
                'POS' => [
                    'Source' => [
                        [
                            'PseudoCityCode' => '8NJL',
                            'RequestorID' => [
                                'Type' => '1',
                                'ID' => '1',
                                'CompanyName' => ['Code' => 'TN'],
                            ],
                        ],
                    ],
                ],
                'OriginDestinationInformation' => $originDestinations,
                'TravelPreferences' => $travelPreferences,
                'TravelerInfoSummary' => $travelerInfoSummary,
                'TPA_Extensions' => [
                    'IntelliSellTransaction' => [
                        'RequestType' => [
                            'Name' => '50ITINS',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function formatSabreDateTime(string $date): string
    {
        $parsed = Carbon::parse($date);
        $parsed->setTime(20, 0, 0);
        return $parsed->format('Y-m-d\TH:i:s');
    }

    private function extractItineraries(array $grouped): array
    {
        $scheduleById = collect($grouped['scheduleDescs'] ?? [])->keyBy('id');
        $legById = collect($grouped['legDescs'] ?? [])->keyBy('id');
        $baggageAllowanceById = collect($grouped['baggageAllowanceDescs'] ?? [])->keyBy('id');

        $itineraryGroups = $grouped['itineraryGroups'] ?? [];
        if (empty($itineraryGroups)) {
            return [];
        }

        $groupRow = $itineraryGroups[0];
        $legDescriptions = $groupRow['groupDescription']['legDescriptions'] ?? [];
        $itineraries = $groupRow['itineraries'] ?? [];
        $results = [];

        foreach ($itineraries as $itinerary) {
            $pricingBlock = $itinerary['pricingInformation'][0] ?? [];
            $fareSeatMeta = $this->extractFareSeatMeta($pricingBlock);
            $pricingTags = $this->inferFareTagsFromPricingBlock($pricingBlock);
            $passengerFare = data_get($pricingBlock, 'fare.passengerInfoList.0.passengerInfo');
            $bagsSummary = $this->summarizeBaggageAllowances(
                data_get($passengerFare, 'baggageInformation', []),
                $baggageAllowanceById,
            );

            $fareCursor = 0;
            $legs = [];
            $legIndex = -1;

            foreach ($itinerary['legs'] ?? [] as $legRef) {
                $legIndex++;
                $leg = $legById->get($legRef['ref'] ?? null);
                if (!$leg) {
                    continue;
                }

                $baseDate = (string) (data_get($legDescriptions, $legIndex . '.departureDate') ?? '');
                if ($baseDate === '') {
                    $baseDate = now()->format('Y-m-d');
                }

                $legs[] = $this->buildSabreLeg(
                    $leg,
                    $scheduleById,
                    $baseDate,
                    $fareSeatMeta,
                    $fareCursor,
                );
            }

            $fare = data_get($pricingBlock, 'fare.totalFare');

            $results[] = [
                'id' => $itinerary['id'] ?? null,
                'totalPrice' => $fare['totalPrice'] ?? null,
                'currency' => $fare['currency'] ?? null,
                'legs' => $legs,
                'supplier' => 'sabre',
                'validating_carrier' => data_get($pricingBlock, 'fare.validatingCarrierCode'),
                'pricing_subsource' => (string) ($pricingBlock['pricingSubsource'] ?? ''),
                'pricing_source' => (string) ($itinerary['pricingSource'] ?? ''),
                'governing_carriers' => data_get($pricingBlock, 'fare.governingCarriers'),
                'non_refundable' => (bool) data_get($passengerFare, 'nonRefundable', false),
                'baggage_notes' => $bagsSummary['label'],
                'fare_tags' => $pricingTags['tags'],
                'listing_meta' => $this->buildListingMeta($legs, $fare ? (float) $fare['totalPrice'] : 0.0, $pricingTags),
            ];
        }

        return $results;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function extractFareSeatMeta(array $pricingBlock): array
    {
        $fareComponents = data_get($pricingBlock, 'fare.passengerInfoList.0.passengerInfo.fareComponents');
        if (!is_array($fareComponents)) {
            return [];
        }

        $segments = [];

        foreach ($fareComponents as $component) {
            foreach (($component['segments'] ?? []) as $segWrap) {
                $segment = $segWrap['segment'] ?? [];
                if ($segment !== []) {
                    $segments[] = $segment;
                }
            }
        }

        return $segments;
    }

    /** @param \Illuminate\Support\Collection<int|string, mixed> $baggageAllowanceById */
    private function summarizeBaggageAllowances(array $baggageInformation, $baggageAllowanceById): array
    {
        $pieces = [];
        foreach ($baggageInformation as $row) {
            $refId = data_get($row, 'allowance.ref');
            if (!$refId) {
                continue;
            }
            $desc = $baggageAllowanceById->get($refId);
            $pc = (int) (data_get($desc, 'pieceCount') ?? 0);
            if ($pc > 0) {
                $pieces[] = $pc;
            }
        }

        if (empty($pieces)) {
            return ['label' => null, 'min_pieces' => null];
        }

        return [
            'min_pieces' => min($pieces),
            'label' => collect($pieces)->max() <= 1
                ? (string) reset($pieces) . ' pc'
                : (string) collect($pieces)->max() . ' pc',
        ];
    }

    /**
     * @return array{tags:list<string>}
     */
    private function inferFareTagsFromPricingBlock(array $pricingBlock): array
    {
        $tags = [];

        $sub = strtolower((string) ($pricingBlock['pricingSubsource'] ?? ''));

        if ($sub !== '') {
            if (str_contains($sub, 'ndc')) {
                $tags[] = 'ndc';
            }
            if ($sub === 'mip' || str_contains($sub, 'pub') || str_contains($sub, 'atpco')) {
                $tags[] = 'published';
            }
            if (!$tags) {
                $tags[] = 'other';
            }
        } elseif (strtoupper((string) ($pricingBlock['pricingSource'] ?? '')) !== '') {
            $tags[] = 'other';
        }

        if (!$tags) {
            $tags[] = 'published';
        }

        return ['tags' => array_values(array_unique($tags)), 'pricingSubsourceNormalized' => $sub];
    }

    /**
     * @param list<string, mixed> $fareSeatMeta
     *
     * @return array<string,mixed>
     */
    private function buildSabreLeg(
        array $leg,
        $scheduleById,
        string $baseDate,
        array $fareSeatMeta,
        int &$fareCursor,
    ): array {
        $segmentsOut = [];

        $dayAccumulator = 0;
        $elapsedFallback = 0;

        foreach ($leg['schedules'] ?? [] as $scheduleRef) {
            $dayAccumulator += (int) ($scheduleRef['departureDateAdjustment'] ?? 0);
            $schedule = $scheduleById->get($scheduleRef['ref'] ?? null);
            if (!$schedule) {
                continue;
            }

            $seatRow = $fareSeatMeta[$fareCursor] ?? [];

            ++$fareCursor;

            $depRaw = $schedule['departure']['time'] ?? '00:00:00';
            $arrRaw = $schedule['arrival']['time'] ?? '00:00:00';
            $depClock = Carbon::parse($this->normalizeSabreTimeForParse($depRaw))->format('H:i');
            $arrParts = explode(':', Carbon::parse($this->normalizeSabreTimeForParse($arrRaw))->format('H:i'));
            $arrH = (int) ($arrParts[0] ?? 0);
            $arrM = (int) ($arrParts[1] ?? 0);

            $depDateTime = Carbon::parse($baseDate . ' ' . $depClock)->addDays($dayAccumulator)->startOfMinute();
            $arrDateTime = $depDateTime->copy()->setTime($arrH, $arrM, 0);

            while ($arrDateTime->lessThanOrEqualTo($depDateTime)) {
                $arrDateTime->addDay();
            }

            $elapsedFallback += max(15, $depDateTime->diffInMinutes($arrDateTime));

            $diffDays = max(0, $depDateTime->diffInDays($arrDateTime, false));

            $marketing = $schedule['carrier']['marketing'] ?? '';
            $mktFlight = trim((string) ($schedule['carrier']['marketingFlightNumber'] ?? ''));

            $segmentsOut[] = [
                'schedule_id' => $schedule['id'] ?? null,
                'from' => $schedule['departure']['airport'] ?? '',
                'to' => $schedule['arrival']['airport'] ?? '',
                'departure_city' => $schedule['departure']['city'] ?? '',
                'arrival_city' => $schedule['arrival']['city'] ?? '',
                'departure_time' => $depRaw,
                'arrival_time' => $arrRaw,
                'departure_terminal' => $schedule['departure']['terminal'] ?? null,
                'arrival_terminal' => $schedule['arrival']['terminal'] ?? null,
                'carrier' => $marketing,
                'carrier_display' => trim($marketing . ('' !== $mktFlight ? ' ' . $mktFlight : '')),
                'carrier_name' => trim($marketing . ('' !== $mktFlight ? '  -  ' . $mktFlight : '')),
                'flight_number' => $schedule['carrier']['marketingFlightNumber'] ?? '',
                'flight_label' => trim($marketing . ('' !== $mktFlight ? ' ' . $mktFlight : '')),
                'operating_carrier' => $schedule['carrier']['operating'] ?? '',
                'operating_flight_number' => $schedule['carrier']['operatingFlightNumber'] ?? '',
                'equipment' => data_get($schedule, 'carrier.equipment.code'),
                'equipment_type_first' => data_get($schedule, 'carrier.equipment.typeForFirstLeg'),
                'equipment_type_last' => data_get($schedule, 'carrier.equipment.typeForLastLeg'),
                'stop_count' => $schedule['stopCount'] ?? 0,
                'departure_datetime' => $depDateTime->toIso8601String(),
                'arrival_datetime' => $arrDateTime->toIso8601String(),
                'departure_label' => $depDateTime->format('M j') . "'" . substr($depDateTime->format('y'), -2),
                'departure_weekday' => $depDateTime->format('D'),
                'arrival_label' => $arrDateTime->format('M j') . "'" . substr($arrDateTime->format('y'), -2),
                'arrival_weekday' => $arrDateTime->format('D'),
                'next_day_hint' => $diffDays >= 1,
                'departure_clock' => $depDateTime->format('H:i'),
                'arrival_clock' => $arrDateTime->format('H:i'),
                'is_red_eye_segment' => $this->clockIsRedEye($depDateTime->format('H:i')),
                'booking_code' => data_get($seatRow, 'bookingCode'),
                'meal_code' => data_get($seatRow, 'mealCode'),
                'cabin_code' => data_get($seatRow, 'cabinCode'),
                'seats_available' => data_get($seatRow, 'seatsAvailable'),
            ];
        }

        $filterAxes = $this->axisForLegSegments($segmentsOut);

        return [
            'elapsedTime' => (int) ($leg['elapsedTime'] ?? $elapsedFallback),
            'segments' => $segmentsOut,
            'filter_axes' => $filterAxes,
        ];
    }

    private function normalizeSabreTimeForParse(?string $t): string
    {
        if (!$t) {
            return '00:00';
        }

        return (string) preg_replace('/([+-][0-9]{2}:?[0-9]{2}|Z)$/', '', $t);
    }

    /** Departure/arrival clocks are HH:mm (24h) */
    private function clockIsRedEye(string $clock): bool
    {
        $parts = explode(':', $clock);
        $h = (int) ($parts[0] ?? 0);
        /** Night departures referenced in dashboards */
        return $h >= 22 || $h < 6;
    }

    /**
     * Sabre-aligned buckets shown in portals: dawn / day / dusk / overnight.
     */
    private function timeBucket(?string $hhmm): int
    {
        if (!$hhmm || !preg_match('/^(\d{1,2}):/', $hhmm, $m)) {
            return 0;
        }
        $h = (int) $m[1];
        if ($h >= 5 && $h < 12) {
            return 1;
        }
        if ($h >= 12 && $h < 18) {
            return 2;
        }
        if ($h >= 18 && $h < 24) {
            return 3;
        }

        return 4;
    }

    /**
     * @param list<array<string,mixed>> $segmentsOut
     */
    private function axisForLegSegments(array $segmentsOut): array
    {
        if (!$segmentsOut) {
            return [
                'stops_tier' => 0,
                'connections' => 0,
                'within_stops' => 0,
                'first_dep_airports' => [],
                'middle_airports' => [],
                'dep_buckets' => [],
                'arr_buckets' => [],
                'carriers' => [],
            ];
        }

        $conn = max(0, count($segmentsOut) - 1);
        $within = 0;

        foreach ($segmentsOut as $seg) {
            $within += (int) ($seg['stop_count'] ?? 0);
        }

        $total = $conn + $within;
        $stopsTier = $total === 0 ? 0 : ($total === 1 ? 1 : 2);

        $first = reset($segmentsOut);
        $last = end($segmentsOut);

        $middles = [];

        foreach ($segmentsOut as $idx => $seg) {
            if ($idx !== count($segmentsOut) - 1) {
                $arr = strtoupper(trim((string) ($seg['to'] ?? '')));
                if ($arr !== '') {
                    $middles[] = $arr;
                }
            }
        }

        $depBuckets = [];
        foreach ($segmentsOut as $seg) {
            $b = $this->timeBucket((string) ($seg['departure_clock'] ?? ''));
            if ($b > 0) {
                $depBuckets[$b] = true;
            }
        }
        $arrBuckets = [];
        foreach ($segmentsOut as $seg) {
            $b = $this->timeBucket((string) ($seg['arrival_clock'] ?? ''));
            if ($b > 0) {
                $arrBuckets[$b] = true;
            }
        }

        return [
            'stops_tier' => $stopsTier,
            'connections' => $conn,
            'within_stops' => $within,
            'first_dep_airports' => [strtoupper((string) ($first['from'] ?? ''))],
            'middle_airports' => array_values(array_unique($middles)),
            'dep_buckets' => array_map('intval', array_keys($depBuckets)),
            'arr_buckets' => array_map('intval', array_keys($arrBuckets)),
            'carriers' => array_values(array_unique(array_filter(array_map(
                static fn ($s) => strtoupper(trim((string) ($s['carrier'] ?? ''))),
                $segmentsOut,
            )))),
        ];
    }

    /**
     * @param list<array<string,mixed>> $legs
     *
     * @return array<string,mixed>
     */
    private function buildListingMeta(array $legs, float $price, array $pricingTags): array
    {
        $defaults = [
            'stops_tier' => 0,
            'connections' => 0,
            'within_stops' => 0,
            'first_dep_airports' => [],
            'middle_airports' => [],
            'dep_buckets' => [],
            'arr_buckets' => [],
            'carriers' => [],
        ];

        $o = isset($legs[0]['filter_axes']) && is_array($legs[0]['filter_axes'])
            ? array_merge($defaults, $legs[0]['filter_axes'])
            : $defaults;

        $r = isset($legs[1]['filter_axes']) && is_array($legs[1]['filter_axes'])
            ? array_merge($defaults, $legs[1]['filter_axes'])
            : null;

        $airlines = [];

        foreach ($legs as $leg) {
            foreach (($leg['segments'] ?? []) as $seg) {
                $c = strtoupper(trim((string) ($seg['carrier'] ?? '')));

                if ($c !== '') {
                    $airlines[$c] = true;
                }
            }
        }

        return [
            'price' => $price,
            'st_o' => $o['stops_tier'],
            'st_r' => $r['stops_tier'] ?? null,
            'dba_o' => $o['dep_buckets'],
            'aba_o' => $o['arr_buckets'],
            'dba_r' => $r['dep_buckets'],
            'aba_r' => $r['arr_buckets'],
            'dep_o' => $o['first_dep_airports'],
            'dep_r' => $r ? $r['first_dep_airports'] : [],
            'conn_o' => $o['middle_airports'],
            'conn_r' => $r ? $r['middle_airports'] : [],
            'al' => array_keys($airlines),
            'fare' => $pricingTags['tags'],
            'first_dep_iso' => data_get($legs, '0.segments.0.departure_datetime'),
            'dur_o' => (int) data_get($legs, '0.elapsedTime', 0),
        ];
    }

    /**
     * @param list<array<string,mixed>> $results
     *
     * @return array<string, mixed>
     */
    private function buildFilterCatalog(array $results): array
    {
        $airCounts = [];
        $depOutbound = [];
        $depReturn = [];
        $connOutbound = [];
        $connReturn = [];

        foreach ($results as $r) {
            $m = $r['listing_meta'] ?? [];
            if (!is_array($m)) {
                continue;
            }
            foreach ($m['al'] ?? [] as $code) {
                $uc = strtoupper(trim((string) $code));

                if ($uc === '') {
                    continue;
                }
                $airCounts[$uc] = ($airCounts[$uc] ?? 0) + 1;
            }
            foreach (($m['dep_o'] ?? []) as $a) {
                $ua = strtoupper(trim((string) $a));

                if ($ua !== '') {
                    $depOutbound[$ua] = true;
                }
            }
            foreach (($m['dep_r'] ?? []) as $a) {
                $ua = strtoupper(trim((string) $a));

                if ($ua !== '') {
                    $depReturn[$ua] = true;
                }
            }
            foreach (($m['conn_o'] ?? []) as $a) {
                $ua = strtoupper(trim((string) $a));

                if ($ua !== '') {
                    $connOutbound[$ua] = true;
                }
            }
            foreach (($m['conn_r'] ?? []) as $a) {
                $ua = strtoupper(trim((string) $a));

                if ($ua !== '') {
                    $connReturn[$ua] = true;
                }
            }
        }

        uksort($airCounts, static fn ($a, $b) => strcasecmp($a, $b));

        $airlinesSorted = [];
        foreach ($airCounts as $code => $count) {
            $airlinesSorted[] = ['code' => $code, 'count' => $count];
        }

        return [
            'airlines' => $airlinesSorted,
            'dep_out' => array_keys($depOutbound),
            'dep_ret' => array_keys($depReturn),
            'conn_out' => array_keys($connOutbound),
            'conn_ret' => array_keys($connReturn),
        ];
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

        $allowed = ['sabre'];
        $providers = array_values(array_intersect($providers, $allowed));

        return empty($providers) ? null : $providers;
    }

    private function isProviderEnabled(string $provider): bool
    {
        if (empty($this->enabledFlightProviders)) {
            return true;
        }

        return in_array(strtolower($provider), $this->enabledFlightProviders, true);
    }
}
