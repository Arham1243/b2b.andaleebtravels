<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FlightController extends Controller
{
    private string $sabreBasicAuth = 'VmpFNk1qVTROak13T2poT1NrdzZRVUU9OlJtRnBjMkZzTVRBPQ==';

    public function index()
    {
        return view('user.flights.index');
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3|different:from',
            'departure_date' => 'required|string',
            'return_date' => 'nullable|string',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'infants' => 'nullable|integer|min:0',
        ]);

        try {
            $token = $this->getSabreToken();
        } catch (\Exception $e) {
            return view('user.flights.search', [
                'results' => [],
                'messages' => [['severity' => 'Error', 'text' => $e->getMessage()]],
                'itineraryCount' => 0,
            ]);
        }

        $payload = $this->buildSabrePayload($validated);
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
            'flight_search_params' => $validated,
        ]);

        return view('user.flights.search', [
            'results' => $results,
            'messages' => $messages,
            'itineraryCount' => $itineraryCount,
        ]);
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
        $origin = strtoupper($data['from']);
        $destination = strtoupper($data['to']);

        $departureDate = $this->formatSabreDateTime($data['departure_date']);
        $returnDate = !empty($data['return_date']) ? $this->formatSabreDateTime($data['return_date']) : null;

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

        $originDestinations = [
            [
                'DepartureDateTime' => $departureDate,
                'OriginLocation' => ['LocationCode' => $origin],
                'DestinationLocation' => ['LocationCode' => $destination],
            ],
        ];

        if ($returnDate) {
            $originDestinations[] = [
                'DepartureDateTime' => $returnDate,
                'OriginLocation' => ['LocationCode' => $destination],
                'DestinationLocation' => ['LocationCode' => $origin],
            ];
        }

        $travelPreferences = [
            'MaxStopsQuantity' => 1,
        ];

        if ($children > 0) {
            $travelPreferences['Baggage'] = [
                'RequestType' => 'C',
                'Description' => true,
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
                'TravelerInfoSummary' => [
                    'AirTravelerAvail' => [
                        [
                            'PassengerTypeQuantity' => $passengerTypes,
                        ],
                    ],
                ],
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
            ];
        }

        return $results;
    }
}
