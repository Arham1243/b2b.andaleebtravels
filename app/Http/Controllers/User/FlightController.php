<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Config;
use Carbon\Carbon;
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
            ]);
        }

        $payload = $this->buildSabrePayload($searchData);
        $response = Http::withToken($token)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('https://api.cert.platform.sabre.com/v5/offers/shop', $payload);

        if (!$response->successful()) {
            return view('user.flights.search', [
                'results' => [],
                'messages' => [['severity' => 'Error', 'text' => 'Flight search failed.']],
                'itineraryCount' => 0,
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
        ]);
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

        $request = Http::asForm()->withHeaders([
            'Authorization' => 'Basic ' . $this->sabreBasicAuth,
        ]);

        $response = $request->post('https://api.cert.platform.sabre.com/v2/auth/token', [
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

        $itineraryGroups = $grouped['itineraryGroups'] ?? [];
        if (empty($itineraryGroups)) {
            return [];
        }

        $itineraries = $itineraryGroups[0]['itineraries'] ?? [];
        $results = [];

        foreach ($itineraries as $itinerary) {
            $legs = [];
            foreach ($itinerary['legs'] ?? [] as $legRef) {
                $leg = $legById->get($legRef['ref'] ?? null);
                if (!$leg) {
                    continue;
                }

                $segments = [];
                foreach ($leg['schedules'] ?? [] as $scheduleRef) {
                    $schedule = $scheduleById->get($scheduleRef['ref'] ?? null);
                    if (!$schedule) {
                        continue;
                    }
                    $segments[] = [
                        'schedule_id' => $schedule['id'] ?? null,
                        'from' => $schedule['departure']['airport'] ?? '',
                        'to' => $schedule['arrival']['airport'] ?? '',
                        'departure_city' => $schedule['departure']['city'] ?? '',
                        'arrival_city' => $schedule['arrival']['city'] ?? '',
                        'departure_time' => $schedule['departure']['time'] ?? '',
                        'arrival_time' => $schedule['arrival']['time'] ?? '',
                        'carrier' => $schedule['carrier']['marketing'] ?? '',
                        'flight_number' => $schedule['carrier']['marketingFlightNumber'] ?? '',
                        'operating_carrier' => $schedule['carrier']['operating'] ?? '',
                        'operating_flight_number' => $schedule['carrier']['operatingFlightNumber'] ?? '',
                        'equipment' => $schedule['carrier']['equipment']['code'] ?? '',
                        'equipment_type_first' => $schedule['carrier']['equipment']['typeForFirstLeg'] ?? '',
                        'equipment_type_last' => $schedule['carrier']['equipment']['typeForLastLeg'] ?? '',
                        'stop_count' => $schedule['stopCount'] ?? 0,
                    ];
                }

                $legs[] = [
                    'elapsedTime' => $leg['elapsedTime'] ?? null,
                    'segments' => $segments,
                ];
            }

            $fare = $itinerary['pricingInformation'][0]['fare']['totalFare'] ?? null;
            $results[] = [
                'id' => $itinerary['id'] ?? null,
                'totalPrice' => $fare['totalPrice'] ?? null,
                'currency' => $fare['currency'] ?? null,
                'legs' => $legs,
                'supplier' => 'sabre',
            ];
        }

        return $results;
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
