<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Services\FlightProviders\FlightProviderManager;
use App\Services\FlightProviders\SabreFlightProvider;
use App\Services\FlightProviders\TravelportFlightProvider;
use App\Services\FlightService;
use App\Services\Travelport\TravelportApiClient;
use App\Support\FlightCabinPreference;
use App\Support\FlightPromoConfig;
use App\Support\SabreBaggagePresenter;
use App\Support\SabreFareRulesRequestBuilder;
use App\Support\SabrePricingResolver;
use App\Support\Travelport\TravelportFareRulesResponseParser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FlightController extends Controller
{
    private ?array $enabledFlightProviders = null;

    public function __construct()
    {
        parent::__construct();

        $config = Config::pluck('config_value', 'config_key')->toArray();
        $adminProviders = $this->parseProviderConfig($config['FLIGHT_SEARCH_PROVIDERS'] ?? null);

        $user = Auth::user();
        $userProviders = $this->parseProviderConfig($user?->flight_search_providers ?? null);

        $this->enabledFlightProviders = $userProviders ?? $adminProviders ?? ['sabre', 'travelport'];
    }

    public function index()
    {
        return view('user.flights.index', [
            'flightPromosEnabled' => FlightPromoConfig::enabled(),
        ]);
    }

    public function search(Request $request)
    {
        if (!$this->hasAnyFlightProviderEnabled()) {
            return view('user.flights.search', [
                'results' => [],
                'messages' => [['severity' => 'Error', 'text' => 'No flight search providers are enabled for your account.']],
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
            'onward_cabin_class' => 'nullable|string|in:Economy,Premium Economy,Business,First',
            'return_cabin_class' => 'nullable|string|in:Economy,Premium Economy,Business,First',
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

        $searchOut = $this->buildFlightProviderManager()->search($searchData);
        $results = $searchOut['results'];
        $messages = $searchOut['messages'];
        $itineraryCount = $searchOut['itineraryCount'];
        $responses = $searchOut['responses'];
        $payloads = $searchOut['payloads'];

        if (vendorPricing()->pricingAdjustmentsEnabled(Auth::user())) {
            $results = applyFlightSearchPricing($results);
        }

        $resultsById = collect($results)->keyBy('id')->toArray();

        session([
            'flight_search_params' => $searchData,
            'flight_search_results' => $resultsById,
            'flight_search_responses' => $responses,
            'flight_search_payload' => $payloads,
            'flight_search_response' => $responses['sabre'] ?? [],
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

    public function debugBaggage(Request $request)
    {
        if (! config('app.debug') && ! app()->environment(['local', 'staging'])) {
            abort(404);
        }

        $validated = $request->validate([
            'itinerary' => 'nullable|integer|min:1',
            'fare' => 'nullable|integer|min:0',
        ]);

        $grouped = session('flight_search_response');
        $results = session('flight_search_results', []);

        if (! is_array($grouped) || $grouped === []) {
            return response()->json([
                'success' => false,
                'error' => 'No search session. Run a flight search first, then open this URL with ?itinerary=ID&fare=0',
            ], 422);
        }

        $itineraryId = (int) ($validated['itinerary'] ?? array_key_first($results));
        $fareIndex = (int) ($validated['fare'] ?? 0);
        $resultCard = $results[$itineraryId] ?? null;

        if (strtolower((string) ($resultCard['supplier'] ?? 'sabre')) !== 'sabre') {
            return response()->json([
                'success' => false,
                'error' => 'Baggage debug is only available for Sabre results.',
            ], 422);
        }

        if (! is_array($resultCard)) {
            return response()->json([
                'success' => false,
                'error' => 'Itinerary not found in session.',
                'available_itinerary_ids' => array_map('intval', array_keys($results)),
            ], 404);
        }

        $pricingBlock = SabrePricingResolver::pricingBlockForFare($resultCard, $grouped, $fareIndex);

        if ($pricingBlock === null) {
            return response()->json([
                'success' => false,
                'error' => 'Fare pricing block not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'itinerary_id' => $itineraryId,
            'fare_index' => $fareIndex,
            'validating_carrier' => data_get($pricingBlock, 'fare.validatingCarrierCode'),
            'fare_brand' => $resultCard['fare_options'][$fareIndex]['fare_brand'] ?? $resultCard['fare_brand'] ?? null,
            'baggage_request_sent' => data_get(session('flight_search_payload'), 'OTA_AirLowFareSearchRQ.TravelPreferences.Baggage'),
            'sabre_baggage_raw' => SabreBaggagePresenter::debugExport($grouped, $pricingBlock),
            'app_baggage_parsed' => SabreBaggagePresenter::fromPricingBlock($pricingBlock, $grouped),
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function fareRulesText(Request $request, FlightService $flightService)
    {
        $validated = $request->validate([
            'itinerary' => 'required|integer|min:1',
            'fare' => 'required|integer|min:0',
        ]);

        $itineraryId = (int) $validated['itinerary'];
        $fareIndex = (int) $validated['fare'];
        $resultCard = session('flight_search_results')[$itineraryId] ?? null;

        if (! is_array($resultCard)) {
            return response()->json([
                'success' => false,
                'error' => 'Search session expired. Please search again.',
            ], 422);
        }

        $supplier = strtolower((string) ($resultCard['supplier'] ?? 'sabre'));

        if ($supplier === 'travelport') {
            return $this->travelportFareRulesText($resultCard, $fareIndex);
        }

        if (! $this->isProviderEnabled('sabre')) {
            return response()->json([
                'success' => false,
                'error' => 'Sabre is disabled for your account.',
            ], 403);
        }

        $grouped = session('flight_search_response');

        if (! is_array($grouped)) {
            return response()->json([
                'success' => false,
                'error' => 'Search session expired. Please search again.',
            ], 422);
        }

        $pricingBlock = SabrePricingResolver::pricingBlockForFare($resultCard, $grouped, $fareIndex);
        if ($pricingBlock === null) {
            return response()->json([
                'success' => false,
                'error' => 'Fare not found for this itinerary.',
            ], 404);
        }

        $searchParams = session('flight_search_params', []);
        $departureDate = is_array($searchParams) ? ($searchParams['departure_date'] ?? null) : null;
        $ruleRequests = SabreFareRulesRequestBuilder::fromPricingBlock($pricingBlock, $grouped, $departureDate);

        if ($ruleRequests === []) {
            return response()->json([
                'success' => false,
                'error' => 'Fare rule details are not available for this fare.',
            ], 422);
        }

        try {
            $components = $flightService->fetchFareRulesText($ruleRequests);

            return response()->json([
                'success' => true,
                'components' => $components,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'Unable to load full fare rules right now. Please try again.',
            ], 500);
        }
    }

    /**
     * @param  array<string, mixed>  $resultCard
     */
    private function travelportFareRulesText(array $resultCard, int $fareIndex)
    {
        if (! $this->isProviderEnabled('travelport')) {
            return response()->json([
                'success' => false,
                'error' => 'Travelport is disabled for your account.',
            ], 403);
        }

        $fareOption = $resultCard['fare_options'][$fareIndex] ?? null;
        $ruleRequest = is_array($fareOption) ? ($fareOption['travelport_fare_rule'] ?? null) : null;

        if (! is_array($ruleRequest) || trim((string) ($ruleRequest['fare_rule_key'] ?? '')) === '') {
            return response()->json([
                'success' => false,
                'error' => 'Fare rule details are not available for this fare.',
            ], 422);
        }

        try {
            $response = (new TravelportApiClient())->airFareRules($ruleRequest);

            if (! ($response['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'error' => $response['error'] ?? 'Unable to load fare rules from Travelport.',
                ], 500);
            }

            $components = TravelportFareRulesResponseParser::toComponents(
                (string) ($response['raw'] ?? ''),
                $ruleRequest,
            );

            if ($components === []) {
                return response()->json([
                    'success' => false,
                    'error' => 'No detailed fare rules returned for this fare.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'components' => $components,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'Unable to load full fare rules right now. Please try again.',
            ], 500);
        }
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
            'onward_cabin_class' => FlightCabinPreference::normalizeUiLabel($data['onward_cabin_class'] ?? 'Economy'),
            'return_cabin_class' => FlightCabinPreference::normalizeUiLabel(
                $data['return_cabin_class'] ?? ($data['onward_cabin_class'] ?? 'Economy')
            ),
        ];

        if ($tripType === 'multi_city') {
            $segments = collect($data['segments'] ?? [])
                ->map(function ($segment) {
                    return [
                        'from' => strtoupper(trim((string) ($segment['from'] ?? ''))),
                        'to' => strtoupper(trim((string) ($segment['to'] ?? ''))),
                        'departure_date' => $this->normalizeFlightSearchDate($segment['departure_date'] ?? '') ?? '',
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
        $normalized['departure_date'] = $this->normalizeFlightSearchDate($data['departure_date'] ?? '') ?? '';
        $normalized['return_date'] = $tripType === 'round_trip'
            ? ($this->normalizeFlightSearchDate($data['return_date'] ?? '') ?? '')
            : null;

        return $normalized;
    }

    private function normalizeFlightSearchDate(mixed $date): ?string
    {
        $date = trim((string) ($date ?? ''));
        if ($date === '') {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $date;
        }
    }


    private function buildFlightProviderManager(): FlightProviderManager
    {
        $providers = [];

        if ($this->isProviderEnabled('sabre')) {
            $providers[] = new SabreFlightProvider();
        }

        if ($this->isProviderEnabled('travelport')) {
            $providers[] = new TravelportFlightProvider();
        }

        return new FlightProviderManager($providers);
    }

    private function hasAnyFlightProviderEnabled(): bool
    {
        return $this->isProviderEnabled('sabre') || $this->isProviderEnabled('travelport');
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

        $allowed = ['sabre', 'travelport'];
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
