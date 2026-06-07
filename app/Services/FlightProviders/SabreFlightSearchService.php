<?php

namespace App\Services\FlightProviders;

use App\Support\FlightCabinPreference;
use App\Support\SabreBaggagePresenter;
use App\Support\SabreFareAmountPresenter;
use App\Support\SabreFareBrandPresenter;
use App\Support\SabreFareRulesPresenter;
use App\Support\SabrePriceNormalizer;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class SabreFlightSearchService
{
    private string $sabreBasicAuth = 'VmpFNk1qVTROak13T2poT1NrdzZRVUU9OlJtRnBjMkZzTVRBPQ==';

    /**
     * @param  array<string, mixed>  $searchData
     * @return array{results: list<array<string, mixed>>, messages: list<array{severity: string, text: string}>, itineraryCount: int, rawResponse: array<string, mixed>, requestPayload: array<string, mixed>}
     */
    public function search(array $searchData): array
    {
        $token = $this->getSabreToken();
        $payload = $this->buildSabrePayload($searchData);
        $response = $this->sabreHttp()->withToken($token)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('https://api.cert.platform.sabre.com/v5/offers/shop', $payload);

        if (!$response->successful()) {
            return [
                'results' => [],
                'messages' => [['severity' => 'Error', 'text' => 'Sabre flight search failed.']],
                'itineraryCount' => 0,
                'rawResponse' => [],
                'requestPayload' => $payload,
            ];
        }

        $data = $response->json();
        $grouped = $data['groupedItineraryResponse'] ?? [];
        $messages = $grouped['messages'] ?? [];
        $itineraryCount = (int) ($grouped['statistics']['itineraryCount'] ?? 0);
        $results = $this->extractItineraries($grouped, $searchData);

        return [
            'results' => $results,
            'messages' => $messages,
            'itineraryCount' => $itineraryCount,
            'rawResponse' => $grouped,
            'requestPayload' => $payload,
        ];
    }

    private function sabreHttp(): PendingRequest
    {
        return Http::timeout((int) config('services.sabre.http_timeout', 90))
            ->connectTimeout((int) config('services.sabre.http_connect_timeout', 30));
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

        $onwardCabin = FlightCabinPreference::normalizeUiLabel($data['onward_cabin_class'] ?? 'Economy');
        $returnCabin = FlightCabinPreference::normalizeUiLabel($data['return_cabin_class'] ?? $onwardCabin);

        if ($tripType === 'multi_city') {
            foreach ($data['segments'] ?? [] as $segment) {
                $originDestinations[] = array_merge(
                    [
                        'DepartureDateTime' => $this->formatSabreDateTime($segment['departure_date']),
                        'OriginLocation' => ['LocationCode' => strtoupper($segment['from'])],
                        'DestinationLocation' => ['LocationCode' => strtoupper($segment['to'])],
                    ],
                    ['TPA_Extensions' => FlightCabinPreference::sabreTpaExtension($onwardCabin)],
                );
            }
        } else {
            $departureDate = $this->formatSabreDateTime($data['departure_date']);
            $returnDate = !empty($data['return_date']) ? $this->formatSabreDateTime($data['return_date']) : null;

            $originDestinations[] = array_merge(
                [
                    'DepartureDateTime' => $departureDate,
                    'OriginLocation' => ['LocationCode' => $origin],
                    'DestinationLocation' => ['LocationCode' => $destination],
                ],
                ['TPA_Extensions' => FlightCabinPreference::sabreTpaExtension($onwardCabin)],
            );

            if ($tripType === 'round_trip' && $returnDate) {
                $originDestinations[] = array_merge(
                    [
                        'DepartureDateTime' => $returnDate,
                        'OriginLocation' => ['LocationCode' => $destination],
                        'DestinationLocation' => ['LocationCode' => $origin],
                    ],
                    ['TPA_Extensions' => FlightCabinPreference::sabreTpaExtension($returnCabin)],
                );
            }
        }

        $travelPreferences = [
            'MaxStopsQuantity' => !empty($data['direct_flight']) ? 0 : 1,
            'CabinPref' => [
                [
                    'Cabin' => FlightCabinPreference::toSabreCode($onwardCabin),
                    'PreferLevel' => 'Only',
                ],
            ],
            'Baggage' => [
                'RequestType' => 'A',
                'Description' => true,
                'CarryOnInfo' => true,
            ],
        ];

        if ($children > 0 && $infants === 0) {
            $travelPreferences['Baggage']['RequestType'] = 'C';
        }

        $seatsRequested = max(1, $adults + $children);

        $travelerInfoSummary = [
            'AirTravelerAvail' => [
                [
                    'PassengerTypeQuantity' => $passengerTypes,
                ],
            ],
            'SeatsRequested' => [$seatsRequested],
            'PriceRequestInformation' => [
                'TPA_Extensions' => [
                    'BrandedFareIndicators' => FlightCabinPreference::sabreBrandedFareIndicators(),
                    'Indicators' => [
                        'RefundPenalty' => ['Ind' => true],
                        'ResTicketing' => ['Ind' => true],
                    ],
                ],
            ],
        ];

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

    private function extractItineraries(array $grouped, array $searchData = []): array
    {
        $tripType = (string) ($searchData['trip_type'] ?? 'one_way');
        $onwardCabin = FlightCabinPreference::normalizeUiLabel($searchData['onward_cabin_class'] ?? 'Economy');
        $returnCabin = FlightCabinPreference::normalizeUiLabel($searchData['return_cabin_class'] ?? $onwardCabin);

        $scheduleById = collect($grouped['scheduleDescs'] ?? [])->keyBy('id');
        $legById = collect($grouped['legDescs'] ?? [])->keyBy('id');
        $baggageAllowanceById = collect($grouped['baggageAllowanceDescs'] ?? [])->keyBy('id');

        $itineraryGroups = $grouped['itineraryGroups'] ?? [];
        if (empty($itineraryGroups)) {
            return [];
        }

        $results = [];
        $sessionKey = 0;

        foreach ($itineraryGroups as $groupIndex => $groupRow) {
            $legDescriptions = $groupRow['groupDescription']['legDescriptions'] ?? [];
            $itineraries = $groupRow['itineraries'] ?? [];

            foreach ($itineraries as $itinerary) {
                $pricingBlocks = $this->collectSabrePricingBlocks($itinerary['pricingInformation'] ?? []);
                if ($pricingBlocks === []) {
                    continue;
                }

                $sessionKey++;
                $fareOptions = [];

                foreach ($pricingBlocks as $pricingEntry) {
                    $fareOptions[] = $this->buildFareOptionData(
                        $pricingEntry['block'],
                        $grouped,
                        $pricingEntry['index'],
                        $baggageAllowanceById,
                    );
                }

                $fareOptions = $this->filterFareOptionsByCabin(
                    $fareOptions,
                    $onwardCabin,
                    $returnCabin,
                    $tripType,
                );

                if ($fareOptions === []) {
                    continue;
                }

                $primaryFare = $fareOptions[0];
                $fareSeatMeta = $primaryFare['fare_seat_meta'];
                $pricingTags = ['tags' => $primaryFare['fare_tags']];

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

                foreach ($fareOptions as &$fareOption) {
                    $fareOption['baggage_details'] = SabreBaggagePresenter::alignWithFlightLegs(
                        $fareOption['baggage_details'] ?? [],
                        $legs,
                    );
                }
                unset($fareOption);

                $primaryFare = $fareOptions[0];
                $legs = $this->expandLegsForConnectingDisplay($legs, $primaryFare['baggage_details'] ?? []);
                $legs = $this->sanitizeLegsForDisplay($legs, $searchData);

                $totalPrice = (float) ($primaryFare['totalPrice'] ?? 0.0);

                $results[] = [
                    'id' => $sessionKey,
                    'sabre_itinerary_id' => (int) ($itinerary['id'] ?? 0),
                    'sabre_group_index' => (int) $groupIndex,
                    'supplierPrice' => $totalPrice,
                    'supplierBasePrice' => $primaryFare['supplierBasePrice'] ?? null,
                    'supplierTaxes' => $primaryFare['supplierTaxes'] ?? null,
                    'basePrice' => $primaryFare['basePrice'] ?? null,
                    'taxes' => $primaryFare['taxes'] ?? null,
                    'totalPrice' => $totalPrice,
                    'currency' => $primaryFare['currency'],
                    'legs' => $legs,
                    'supplier' => 'sabre',
                    'validating_carrier' => $primaryFare['validating_carrier'],
                    'pricing_subsource' => $primaryFare['pricing_subsource'],
                    'pricing_source' => (string) ($itinerary['pricingSource'] ?? ''),
                    'governing_carriers' => $primaryFare['governing_carriers'],
                    'non_refundable' => $primaryFare['non_refundable'],
                    'fare_brand' => $primaryFare['fare_brand'],
                    'baggage_notes' => $primaryFare['baggage_notes'],
                    'baggage_details' => $primaryFare['baggage_details'],
                    'fare_rules' => $primaryFare['fare_rules'],
                    'fare_tags' => $primaryFare['fare_tags'],
                    'fare_options' => $fareOptions,
                    'listing_meta' => $this->buildListingMeta($legs, $totalPrice, $pricingTags),
                ];
            }
        }

        return $results;
    }

    /**
     * @param  list<array<string, mixed>>  $fareOptions
     * @return list<array<string, mixed>>
     */
    private function filterFareOptionsByCabin(
        array $fareOptions,
        string $onwardCabin,
        string $returnCabin,
        string $tripType,
    ): array {
        return array_values(array_filter(
            $fareOptions,
            static fn (array $fare): bool => FlightCabinPreference::fareMatchesSearch(
                $fare,
                $onwardCabin,
                $returnCabin,
                $tripType,
            ),
        ));
    }

    /**
     * @param  mixed  $pricingInformation
     *
     * @return list<array{index: int, block: array<string, mixed>}>
     */
    private function normalizePricingInformation(mixed $pricingInformation): array
    {
        if (! is_array($pricingInformation) || $pricingInformation === []) {
            return [];
        }

        if (array_key_exists('fare', $pricingInformation)) {
            return [['index' => 0, 'block' => $pricingInformation]];
        }

        $entries = [];

        foreach ($pricingInformation as $index => $block) {
            if (! is_array($block) || ! array_key_exists('fare', $block)) {
                continue;
            }

            $entries[] = [
                'index' => (int) $index,
                'block' => $block,
            ];
        }

        return $entries;
    }

    /**
     * @param  mixed  $pricingInformation
     *
     * @return list<array{index: int, block: array<string, mixed>, price: float}>
     */
    private function collectSabrePricingBlocks(mixed $pricingInformation): array
    {
        $entries = $this->normalizePricingInformation($pricingInformation);
        if ($entries === []) {
            return [];
        }

        $blocks = [];

        foreach ($entries as $entry) {
            $price = $this->extractSabreTotalPrice($entry['block']);
            if ($price === null) {
                continue;
            }

            $blocks[] = [
                'index' => $entry['index'],
                'block' => $entry['block'],
                'price' => $price,
            ];
        }

        if ($blocks !== []) {
            usort($blocks, static fn (array $a, array $b): int => $a['price'] <=> $b['price']);

            return $blocks;
        }

        $fallback = $entries[0];

        return [[
            'index' => $fallback['index'],
            'block' => $fallback['block'],
            'price' => $this->extractSabreTotalPrice($fallback['block']) ?? 0.0,
        ]];
    }

    /**
     * @param  \Illuminate\Support\Collection<int|string, mixed>  $baggageAllowanceById
     *
     * @return array<string, mixed>
     */
    private function buildFareOptionData(
        array $pricingBlock,
        array $grouped,
        int $pricingIndex,
        $baggageAllowanceById,
    ): array {
        $passengerFare = data_get($pricingBlock, 'fare.passengerInfoList.0.passengerInfo', []);
        $baggageDetails = SabreBaggagePresenter::fromPricingBlock($pricingBlock, $grouped);
        $bagsSummary = $this->summarizeBaggageAllowances(
            is_array($passengerFare) ? ($passengerFare['baggageInformation'] ?? []) : [],
            $baggageAllowanceById,
        );
        $fareSeatMeta = $this->extractFareSeatMeta($pricingBlock);
        $fareRules = SabreFareRulesPresenter::fromPricingBlock($pricingBlock, $grouped);
        $cabinMeta = $this->extractFareCabinMeta($fareSeatMeta, $fareRules);
        $fareAmounts = SabreFareAmountPresenter::fromPricingBlock($pricingBlock);

        return [
            'sabre_pricing_index' => $pricingIndex,
            'totalPrice' => $this->extractSabreTotalPrice($pricingBlock),
            'supplierBasePrice' => $fareAmounts['base'] ?? null,
            'supplierTaxes' => $fareAmounts['tax'] ?? null,
            'basePrice' => $fareAmounts['base'] ?? null,
            'taxes' => $fareAmounts['tax'] ?? null,
            'currency' => data_get($pricingBlock, 'fare.totalFare.currency'),
            'fare_brand' => SabreFareBrandPresenter::fromPricingBlock($pricingBlock, $grouped),
            'fare_basis' => $this->summarizeFareBasis($fareRules),
            'non_refundable' => ! ($fareRules['refundable'] ?? true),
            'baggage_notes' => $baggageDetails['summary'] ?? $bagsSummary['label'],
            'baggage_details' => $baggageDetails,
            'fare_rules' => $fareRules,
            'fare_tags' => $this->inferFareTagsFromPricingBlock($pricingBlock)['tags'],
            'pricing_subsource' => (string) ($pricingBlock['pricingSubsource'] ?? ''),
            'validating_carrier' => data_get($pricingBlock, 'fare.validatingCarrierCode'),
            'governing_carriers' => data_get($pricingBlock, 'fare.governingCarriers'),
            'fare_seat_meta' => $fareSeatMeta,
            'seats_available' => $this->summarizeSeatsAvailable($fareSeatMeta),
            'cabin_code' => $cabinMeta['cabin_code'],
            'booking_code' => $cabinMeta['booking_code'],
        ];
    }

    /**
     * @param  array<string, mixed>  $fareRules
     */
    private function summarizeFareBasis(array $fareRules): ?string
    {
        $codes = [];

        foreach ($fareRules['components'] ?? [] as $component) {
            if (! is_array($component)) {
                continue;
            }

            $code = self::stringOrNull($component['fare_basis'] ?? null);

            if ($code !== null && ! in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        return $codes !== [] ? implode(' / ', $codes) : null;
    }

    /**
     * Minimum seatsAvailable across all segments in a fare (Sabre caps at 9 when availabilityBreak is true).
     *
     * @param  list<array<string, mixed>>  $fareSeatMeta
     */
    private function summarizeSeatsAvailable(array $fareSeatMeta): ?int
    {
        $counts = [];

        foreach ($fareSeatMeta as $segment) {
            $raw = data_get($segment, 'seatsAvailable');

            if ($raw !== null && $raw !== '' && is_numeric($raw)) {
                $counts[] = (int) $raw;
            }
        }

        return $counts !== [] ? min($counts) : null;
    }

    /**
     * @param  list<array<string, mixed>>  $fareSeatMeta
     * @param  array<string, mixed>  $fareRules
     *
     * @return array{cabin_code: ?string, booking_code: ?string}
     */
    private function extractFareCabinMeta(array $fareSeatMeta, array $fareRules): array
    {
        $firstSeatSegment = $fareSeatMeta[0] ?? [];
        $cabinCode = null;

        foreach ($fareRules['components'] ?? [] as $component) {
            if (! is_array($component)) {
                continue;
            }

            $cabinCode = self::stringOrNull($component['cabin'] ?? null);

            if ($cabinCode !== null) {
                break;
            }
        }

        if ($cabinCode === null) {
            $cabinCode = self::stringOrNull(data_get($firstSeatSegment, 'cabinCode'));
        }

        $bookingCode = $this->resolveFareBookingCode($fareRules, $fareSeatMeta);

        return [
            'cabin_code' => $cabinCode,
            'booking_code' => $bookingCode,
        ];
    }

    /**
     * Fare-level RBD — prefer ATPCO fare basis (e.g. IJR3R1SI → I), not the first
     * segment bookingCode which can differ on connecting itineraries (Qatar, etc.).
     *
     * @param  array<string, mixed>  $fareRules
     * @param  list<array<string, mixed>>  $fareSeatMeta
     */
    private function resolveFareBookingCode(array $fareRules, array $fareSeatMeta): ?string
    {
        $fromBasis = [];

        foreach ($fareRules['components'] ?? [] as $component) {
            if (! is_array($component)) {
                continue;
            }

            $rbd = self::bookingCodeFromFareBasis($component['fare_basis'] ?? null);

            if ($rbd !== null) {
                $fromBasis[] = $rbd;
            }
        }

        if ($fromBasis !== []) {
            return $fromBasis[0];
        }

        return self::stringOrNull(data_get($fareSeatMeta[0] ?? [], 'bookingCode'));
    }

    private static function bookingCodeFromFareBasis(mixed $fareBasis): ?string
    {
        $fareBasis = trim((string) ($fareBasis ?? ''));

        if ($fareBasis === '') {
            return null;
        }

        $first = strtoupper($fareBasis[0]);

        return preg_match('/^[A-Z]$/', $first) ? $first : null;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $pricingBlock
     */
    private function extractSabreTotalPrice(array $pricingBlock): ?float
    {
        $totalFare = data_get($pricingBlock, 'fare.totalFare');

        foreach (['totalPrice', 'equivalentAmount', 'constructAmount'] as $key) {
            $amount = SabrePriceNormalizer::normalize(data_get($totalFare, $key));
            if ($amount !== null) {
                return $amount;
            }
        }

        $passengerRows = data_get($pricingBlock, 'fare.passengerInfoList', []);
        if (! is_array($passengerRows) || $passengerRows === []) {
            return null;
        }

        $sum = 0.0;
        $hasAmount = false;

        foreach ($passengerRows as $row) {
            $passengerTotalFare = data_get($row, 'passengerInfo.passengerTotalFare', []);

            $paxAmount = null;
            foreach (['totalPrice', 'equivalentAmount', 'constructAmount'] as $key) {
                $paxAmount = SabrePriceNormalizer::normalize(data_get($passengerTotalFare, $key));
                if ($paxAmount !== null) {
                    break;
                }
            }

            if ($paxAmount === null) {
                continue;
            }

            $sum += $paxAmount;
            $hasAmount = true;
        }

        return $hasAmount ? round($sum, 2) : null;
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
            $provisionType = strtoupper(trim((string) ($row['provisionType'] ?? 'A')));
            if (in_array($provisionType, ['B', 'CARRYON', 'CARRY-ON', 'C'], true)) {
                continue;
            }

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
            $arrClock = Carbon::parse($this->normalizeSabreTimeForParse($arrRaw))->format('H:i');

            $depDateTime = $this->parseSabreScheduleDateTime($baseDate, $depRaw, $dayAccumulator);
            $arrDayAdjustment = $dayAccumulator
                + (int) ($scheduleRef['arrivalDateAdjustment'] ?? 0)
                + (int) ($schedule['arrival']['dateAdjustment'] ?? 0);
            $arrDateTime = $this->parseSabreScheduleDateTime($baseDate, $arrRaw, $arrDayAdjustment);

            $segmentElapsed = (int) ($schedule['elapsedTime'] ?? 0);
            if ($segmentElapsed <= 0) {
                $segmentElapsed = max(15, (int) $depDateTime->diffInMinutes($arrDateTime, false));
            }

            $elapsedFallback += $segmentElapsed;

            $diffDays = max(0, $depDateTime->copy()->startOfDay()->diffInDays($arrDateTime->copy()->startOfDay(), false));

            $marketing = $schedule['carrier']['marketing'] ?? '';
            $mktFlight = trim((string) ($schedule['carrier']['marketingFlightNumber'] ?? ''));

            $segmentsOut[] = [
                'schedule_id' => $schedule['id'] ?? null,
                'from' => $schedule['departure']['airport'] ?? '',
                'to' => $schedule['arrival']['airport'] ?? '',
                'departure_city' => resolveFlightCityLabel($schedule['departure']['city'] ?? '', $schedule['departure']['airport'] ?? ''),
                'arrival_city' => resolveFlightCityLabel($schedule['arrival']['city'] ?? '', $schedule['arrival']['airport'] ?? ''),
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
                'elapsedTime' => $segmentElapsed,
                'departure_datetime' => $depDateTime->toIso8601String(),
                'arrival_datetime' => $arrDateTime->toIso8601String(),
                'departure_label' => formatFlightSegmentDate($depDateTime),
                'departure_weekday' => $depDateTime->format('D'),
                'arrival_label' => formatFlightSegmentDate($arrDateTime),
                'arrival_weekday' => $arrDateTime->format('D'),
                'next_day_hint' => $diffDays >= 1,
                'departure_clock' => $depClock,
                'arrival_clock' => $arrClock,
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

    /**
     * Build an absolute segment timestamp from Sabre's local clock + UTC offset.
     */
    private function parseSabreScheduleDateTime(string $baseDate, ?string $timeRaw, int $dayAdjustment = 0): Carbon
    {
        $timeRaw = trim((string) ($timeRaw ?? ''));
        if ($timeRaw === '') {
            $timeRaw = '00:00:00';
        }

        $date = Carbon::parse($baseDate)->addDays($dayAdjustment)->format('Y-m-d');

        if (preg_match('/([+-]\d{2}:?\d{2}|Z)$/i', $timeRaw)) {
            $timePart = $timeRaw;
            if (preg_match('/^\d{2}:\d{2}[+-]/', $timePart)) {
                $timePart = preg_replace('/^(\d{2}:\d{2})/', '$1:00', $timePart);
            } elseif (preg_match('/^\d{2}:\d{2}:\d{2}[+-]/', $timePart) === 0 && preg_match('/^\d{2}:\d{2}:\d{2}Z/i', $timePart) === 0) {
                $timePart .= ':00';
            }

            $timePart = (string) preg_replace('/([+-])(\d{2})(\d{2})$/', '$1$2:$3', $timePart);

            return Carbon::parse($date . 'T' . $timePart);
        }

        $clock = Carbon::parse($this->normalizeSabreTimeForParse($timeRaw))->format('H:i:s');

        return Carbon::parse($date . ' ' . $clock)->startOfMinute();
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

        if ($h >= 0 && $h < 6) {
            return 4;
        }
        if ($h >= 6 && $h < 12) {
            return 1;
        }
        if ($h >= 12 && $h < 18) {
            return 2;
        }

        return 3;
    }

    /**
     * Some Sabre fares expose per-segment baggage routes before all schedule segments are materialized.
     * Expand display legs so the details popup shows each connecting segment separately.
     *
     * @param  list<array<string, mixed>>  $legs
     * @param  array<string, mixed>  $baggageDetails
     * @return list<array<string, mixed>>
     */
    private function expandLegsForConnectingDisplay(array $legs, array $baggageDetails): array
    {
        $allRoutes = [];

        foreach (array_merge($baggageDetails['checked'] ?? [], $baggageDetails['cabin'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $route = trim((string) ($row['route'] ?? ''));
            if ($route === '' || strcasecmp($route, 'All segments') === 0) {
                continue;
            }

            if (! in_array($route, $allRoutes, true)) {
                $allRoutes[] = $route;
            }
        }

        if ($allRoutes === []) {
            return $legs;
        }

        foreach ($legs as &$leg) {
            $segments = is_array($leg['segments'] ?? null) ? $leg['segments'] : [];

            if ($segments === []) {
                continue;
            }

            $legRoutes = $this->expectedRoutesForLeg($segments, $allRoutes);

            if (count($legRoutes) <= 1 || count($segments) >= count($legRoutes)) {
                continue;
            }

            $expanded = $this->buildDisplaySegmentsFromRoutes($segments, $legRoutes);

            if (count($expanded) > count($segments)) {
                $leg['segments'] = $expanded;
                $leg['filter_axes'] = $this->axisForLegSegments($expanded);
            }
        }
        unset($leg);

        return $legs;
    }

    /**
     * Keep only baggage routes that chain within a single leg's origin → destination.
     *
     * @param  list<array<string, mixed>>  $segments
     * @param  list<string>  $allRoutes
     * @return list<string>
     */
    private function expectedRoutesForLeg(array $segments, array $allRoutes): array
    {
        $first = $segments[0] ?? [];
        $last = $segments[array_key_last($segments)] ?? [];
        $origin = strtoupper(trim((string) ($first['from'] ?? '')));
        $destination = strtoupper(trim((string) ($last['to'] ?? '')));

        if ($origin === '' || $destination === '') {
            return [];
        }

        $parsed = [];

        foreach ($allRoutes as $route) {
            if (! preg_match('/^([A-Z]{3})\s*→\s*([A-Z]{3})$/', strtoupper(trim($route)), $matches)) {
                continue;
            }

            $parsed[] = [
                'route' => strtoupper(trim($matches[1])) . ' → ' . strtoupper(trim($matches[2])),
                'from' => strtoupper(trim($matches[1])),
                'to' => strtoupper(trim($matches[2])),
            ];
        }

        if ($parsed === []) {
            return [];
        }

        $selected = [];
        $remaining = $parsed;
        $current = $origin;
        $guard = count($parsed) + 1;

        while ($current !== $destination && $guard-- > 0) {
            $next = null;

            foreach ($remaining as $index => $route) {
                if ($route['from'] !== $current) {
                    continue;
                }

                $next = $route;
                unset($remaining[$index]);
                break;
            }

            if ($next === null) {
                break;
            }

            $selected[] = $next['route'];
            $current = $next['to'];
        }

        if ($current === $destination && $selected !== []) {
            return $selected;
        }

        foreach ($parsed as $route) {
            if ($route['from'] === $origin && $route['to'] === $destination) {
                return [$route['route']];
            }
        }

        return [];
    }

    /**
     * Drop cross-leg segments that baggage expansion can inject on round trips.
     *
     * @param  list<array<string, mixed>>  $legs
     * @param  array<string, mixed>  $searchData
     * @return list<array<string, mixed>>
     */
    private function sanitizeLegsForDisplay(array $legs, array $searchData): array
    {
        $from = strtoupper(trim((string) ($searchData['from'] ?? '')));
        $to = strtoupper(trim((string) ($searchData['to'] ?? '')));

        if ($from === '' || $to === '') {
            return $legs;
        }

        foreach ($legs as $legIndex => &$leg) {
            $segments = is_array($leg['segments'] ?? null) ? $leg['segments'] : [];
            if ($segments === []) {
                continue;
            }

            $origin = $legIndex === 0 ? $from : $to;
            $destination = $legIndex === 0 ? $to : $from;

            $filtered = $this->filterSegmentsForLegDirection($segments, $origin, $destination);
            if ($filtered !== []) {
                $leg['segments'] = $filtered;
                $leg['filter_axes'] = $this->axisForLegSegments($filtered);
            }
        }
        unset($leg);

        return $legs;
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     * @return list<array<string, mixed>>
     */
    private function filterSegmentsForLegDirection(array $segments, string $origin, string $destination): array
    {
        $origin = strtoupper($origin);
        $destination = strtoupper($destination);

        $filtered = [];

        foreach ($segments as $segment) {
            if (! is_array($segment)) {
                continue;
            }

            $from = strtoupper(trim((string) ($segment['from'] ?? '')));
            $to = strtoupper(trim((string) ($segment['to'] ?? '')));

            if ($from === $destination && $to === $origin) {
                continue;
            }

            $filtered[] = $segment;
        }

        if ($filtered === []) {
            return $segments;
        }

        $chain = $this->pickSegmentChain($filtered, $origin, $destination);

        return $chain !== [] ? $chain : $filtered;
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     * @return list<array<string, mixed>>
     */
    private function pickSegmentChain(array $segments, string $origin, string $destination): array
    {
        $remaining = $segments;
        $selected = [];
        $current = strtoupper($origin);
        $guard = count($segments) + 1;

        while ($current !== strtoupper($destination) && $guard-- > 0) {
            $next = null;
            $nextIndex = null;

            foreach ($remaining as $index => $segment) {
                $from = strtoupper(trim((string) ($segment['from'] ?? '')));
                if ($from !== $current) {
                    continue;
                }

                $next = $segment;
                $nextIndex = $index;
                break;
            }

            if ($next === null) {
                break;
            }

            unset($remaining[$nextIndex]);
            $selected[] = $next;
            $current = strtoupper(trim((string) ($next['to'] ?? '')));
        }

        if ($current !== strtoupper($destination)) {
            return [];
        }

        return $selected;
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     * @param  list<string>  $routes
     * @return list<array<string, mixed>>
     */
    private function buildDisplaySegmentsFromRoutes(array $segments, array $routes): array
    {
        $parsedRoutes = [];

        foreach ($routes as $route) {
            if (! preg_match('/^([A-Z]{3})\s*→\s*([A-Z]{3})$/', strtoupper(trim($route)), $matches)) {
                continue;
            }

            $parsedRoutes[] = [
                'route' => strtoupper(trim($matches[1])) . ' → ' . strtoupper(trim($matches[2])),
                'from' => strtoupper(trim($matches[1])),
                'to' => strtoupper(trim($matches[2])),
            ];
        }

        if ($parsedRoutes === []) {
            return $segments;
        }

        $template = $segments[0] ?? [];
        $expanded = [];

        foreach ($parsedRoutes as $index => $parsedRoute) {
            $matched = null;

            foreach ($segments as $segment) {
                if (! is_array($segment)) {
                    continue;
                }

                if (strtoupper((string) ($segment['from'] ?? '')) === $parsedRoute['from']
                    && strtoupper((string) ($segment['to'] ?? '')) === $parsedRoute['to']) {
                    $matched = $segment;
                    break;
                }
            }

            if ($matched !== null) {
                $expanded[] = $matched;
                continue;
            }

            $expanded[] = array_merge($template, [
                'from' => $parsedRoute['from'],
                'to' => $parsedRoute['to'],
                'departure_city' => resolveFlightCityLabel('', $parsedRoute['from']),
                'arrival_city' => resolveFlightCityLabel('', $parsedRoute['to']),
                'departure_clock' => $index === 0 ? ($template['departure_clock'] ?? ' - ') : ' - ',
                'arrival_clock' => $index === count($parsedRoutes) - 1 ? ($template['arrival_clock'] ?? ' - ') : ' - ',
                'departure_label' => $index === 0 ? ($template['departure_label'] ?? '') : '',
                'arrival_label' => $index === count($parsedRoutes) - 1 ? ($template['arrival_label'] ?? '') : '',
                'departure_datetime' => $index === 0 ? ($template['departure_datetime'] ?? null) : null,
                'arrival_datetime' => $index === count($parsedRoutes) - 1 ? ($template['arrival_datetime'] ?? null) : null,
            ]);
        }

        return $expanded !== [] ? $expanded : $segments;
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

        $outSegs = $legs[0]['segments'] ?? [];
        $firstOutSeg = $outSegs[0] ?? [];
        $lastOutSeg = $outSegs !== [] ? $outSegs[array_key_last($outSegs)] : [];
        $airlinePrimary = strtoupper(trim((string) ($firstOutSeg['carrier'] ?? '')));
        $airlineName = trim((string) ($firstOutSeg['carrier_display'] ?? $airlinePrimary));

        $airlineCodes = array_keys($airlines);
        usort($airlineCodes, static function (string $a, string $b) use ($airlinePrimary): int {
            if ($a === $airlinePrimary && $b !== $airlinePrimary) {
                return -1;
            }
            if ($b === $airlinePrimary && $a !== $airlinePrimary) {
                return 1;
            }

            return strcasecmp($a, $b);
        });

        $durOutbound = (int) data_get($legs, '0.elapsedTime', 0);
        $durReturn = (int) data_get($legs, '1.elapsedTime', 0);

        return [
            'price' => $price,
            'st_o' => $o['stops_tier'],
            'st_r' => $r ? ($r['stops_tier'] ?? null) : null,
            'dba_o' => $o['dep_buckets'],
            'aba_o' => $o['arr_buckets'],
            'dba_r' => $r ? ($r['dep_buckets'] ?? []) : [],
            'aba_r' => $r ? ($r['arr_buckets'] ?? []) : [],
            'dep_o' => $o['first_dep_airports'],
            'dep_r' => $r ? $r['first_dep_airports'] : [],
            'conn_o' => $o['middle_airports'],
            'conn_r' => $r ? $r['middle_airports'] : [],
            'al' => $airlineCodes,
            'airline_primary' => $airlinePrimary,
            'airline_name' => $airlineName,
            'fare' => $pricingTags['tags'],
            'first_dep_iso' => data_get($legs, '0.segments.0.departure_datetime'),
            'first_arr_iso' => $lastOutSeg['arrival_datetime'] ?? null,
            'dep_ts' => $this->listingTimestamp(data_get($legs, '0.segments.0.departure_datetime')),
            'arr_ts' => $this->listingTimestamp($lastOutSeg['arrival_datetime'] ?? null),
            'dur_o' => $durOutbound,
            'dur_r' => $durReturn,
            'dur_total' => $durOutbound + $durReturn,
        ];
    }

    private function listingTimestamp(mixed $iso): int
    {
        if (! is_string($iso) || trim($iso) === '') {
            return 0;
        }

        try {
            return (int) \Carbon\Carbon::parse($iso)->timestamp;
        } catch (\Throwable $e) {
            return 0;
        }
    }

}
