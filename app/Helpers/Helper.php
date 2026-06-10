<?php

use Carbon\Carbon;
use Illuminate\Support\HtmlString;

if (! function_exists('sanitizedLink')) {
    function sanitizedLink($url)
    {
        return '//' . preg_replace('/^(https?:\/\/)?(www\.)?/', '', $url);
    }
}

if (! function_exists('walletBankProofUrl')) {
    /**
     * Proof files are stored under public/; URL is always via asset() (no /storage).
     */
    function walletBankProofUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');

        return asset($path);
    }
}

if (! function_exists('companyCurrency')) {
    /** ISO code for amounts in emails and plain-text contexts (no HTML symbol). */
    function companyCurrency(): string
    {
        return (string) config('app.company_currency', 'AED');
    }
}

if (! function_exists('currencySymbol')) {
    function currencySymbol(): HtmlString
    {
        return new HtmlString('<span class="dirham">D</span>');
    }
}

if (! function_exists('formatPrice')) {
    function formatPrice($price, bool $float = true): HtmlString
    {
        $val = $float
            ? number_format($price, 2, '.', ',')
            : number_format($price, 0, '.', ',');

        return new HtmlString(currencySymbol()->toHtml() . $val);
    }
}

if (! function_exists('formatFlightCabinLabel')) {
    /**
     * Map Sabre/IATA cabin / class codes to readable labels.
     * Q, K, X, O, I stay as single letters; other known codes use full words.
     */
    function formatFlightCabinLabel(?string $code): string
    {
        $code = trim((string) ($code ?? ''));

        if ($code === '') {
            return '';
        }

        if (strlen($code) > 2) {
            return ucwords(strtolower($code));
        }

        $upper = strtoupper($code);

        if (in_array($upper, ['Q', 'K', 'X', 'O', 'I'], true)) {
            return $upper;
        }

        $labels = [
            'Y' => 'Economy',
            'B' => 'Economy',
            'M' => 'Economy',
            'H' => 'Economy',
            'V' => 'Economy',
            'L' => 'Economy',
            'G' => 'Economy',
            'N' => 'Economy',
            'T' => 'Economy',
            'E' => 'Economy',
            'R' => 'Economy',
            'U' => 'Economy',
            'W' => 'Premium Economy',
            'S' => 'Premium Economy',
            'C' => 'Business',
            'J' => 'Business',
            'D' => 'Business',
            'Z' => 'Business',
            'F' => 'First',
            'A' => 'First',
            'P' => 'First',
        ];

        return $labels[$upper] ?? $code;
    }
}

if (! function_exists('formatFlightBookingClassLabel')) {
    /**
     * Label for fare booking class (RBD).
     * Economy codes use full words; letter RBDs use "Class X" so cabin is not repeated.
     */
    function formatFlightBookingClassLabel(?string $code): string
    {
        $code = trim((string) ($code ?? ''));

        if ($code === '') {
            return '';
        }

        $upper = strtoupper($code);

        if (strlen($code) === 1) {
            $economyWords = ['Y', 'B', 'M', 'H', 'V', 'L', 'G', 'N', 'T', 'E', 'R', 'U'];
            $premiumWords = ['W', 'S'];

            if (in_array($upper, $economyWords, true)) {
                return 'Economy';
            }

            if (in_array($upper, $premiumWords, true)) {
                return 'Premium Economy';
            }

            return 'Class ' . $upper;
        }

        return formatFlightCabinLabel($code);
    }
}

if (! function_exists('flightFareRowCabinLabels')) {
    /**
     * One cabin tier per fare row. Sabre cabin_code (e.g. Y) and booking RBD (e.g. S)
     * can map to different tier names — show the fare cabin only, not both.
     *
     * @return array{cabin: string, booking: string}
     */
    function flightFareRowCabinLabels(?string $cabinCode, ?string $bookingCode): array
    {
        $cabinCode = trim((string) ($cabinCode ?? ''));
        $bookingCode = trim((string) ($bookingCode ?? ''));

        $cabinLabel = $cabinCode !== '' ? formatFlightCabinLabel($cabinCode) : '';
        if ($cabinLabel === '' && $bookingCode !== '') {
            $cabinLabel = formatFlightCabinLabel($bookingCode);
        }

        $bookingLabel = '';
        $tierLabels = ['Economy', 'Premium Economy', 'Business', 'First'];

        if ($bookingCode !== '' && strlen($bookingCode) === 1) {
            $upper = strtoupper($bookingCode);
            $bookingAsTier = formatFlightBookingClassLabel($bookingCode);

            if ($bookingAsTier === $cabinLabel) {
                // Same tier (e.g. LH economy K) — show the RBD letter, not a duplicate "Economy".
                $bookingLabel = 'Class ' . $upper;
            } elseif (in_array($bookingAsTier, $tierLabels, true)) {
                // Conflicting tier (e.g. Y cabin + S → Premium Economy) — trust cabin_code only.
                $bookingLabel = '';
            } else {
                $bookingLabel = $bookingAsTier;
            }
        }

        return ['cabin' => $cabinLabel, 'booking' => $bookingLabel];
    }
}

if (! function_exists('formatCreditLimitDisplay')) {
    function formatCreditLimitDisplay(?\App\Models\B2bVendor $vendor, string $emptyLabel = 'Not set'): HtmlString
    {
        if ($vendor === null) {
            return new HtmlString('<span class="text-muted">' . e($emptyLabel) . '</span>');
        }

        return formatPrice($vendor->creditLimitAmount());
    }
}

if (! function_exists('formatCreditMetricDisplay')) {
    /** Credit used/available — show em dash when no credit limit is configured. */
    function formatCreditMetricDisplay(?\App\Models\B2bVendor $vendor, float $amount, string $emptyLabel = '—'): HtmlString
    {
        if ($vendor === null || ! $vendor->hasCreditLimit()) {
            return new HtmlString('<span class="text-muted">' . e($emptyLabel) . '</span>');
        }

        return formatPrice($amount);
    }
}

if (! function_exists('formatBookingSupplierLabel')) {
    /** Supplier label for booking UIs (e.g. TBO must stay uppercase). */
    function formatBookingSupplierLabel(?string $supplier, string $emptyLabel = 'N/A'): string
    {
        $s = strtolower(trim((string) $supplier));

        if ($s === '') {
            return $emptyLabel;
        }

        return match ($s) {
            'tbo' => 'TBO',
            'sabre' => 'Sabre',
            'travelport' => 'Travelport',
            default => ucfirst($s),
        };
    }
}

if (! function_exists('normalizeFlightBookingProvider')) {
    /** Normalize GDS provider slug stored on flight bookings (sabre|travelport). */
    function normalizeFlightBookingProvider(?string $provider): string
    {
        $normalized = strtolower(trim((string) $provider));

        return in_array($normalized, ['travelport', 'sabre'], true) ? $normalized : 'sabre';
    }
}

if (! function_exists('formatFlightBookingProviderLabel')) {
    function formatFlightBookingProviderLabel(?string $provider): string
    {
        return formatBookingSupplierLabel(normalizeFlightBookingProvider($provider), 'Sabre');
    }
}

if (! function_exists('flightHoldReleaseSuccessMessage')) {
    function flightHoldReleaseSuccessMessage(\App\Models\B2bFlightBooking $booking): string
    {
        $pnr = trim((string) ($booking->sabre_record_locator ?? ''));

        if ($pnr === '') {
            return 'Hold released for booking #' . $booking->booking_number . '.';
        }

        return 'Hold released for booking #' . $booking->booking_number . '. PNR ' . $pnr . ' was cancelled at the airline.';
    }
}

if (! function_exists('formatBookingCancelledAt')) {
    /**
     * @param  \Carbon\Carbon|\DateTimeInterface|string|null  $value
     */
    function formatBookingCancelledAt($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d M Y, h:i A');
        } catch (\Throwable) {
            return null;
        }
    }
}

if (! function_exists('formatBookingCancelledByLabel')) {
    function formatBookingCancelledByLabel(?string $by): string
    {
        return match ($by) {
            'vendor' => 'Agent',
            'vendor_release' => 'Hold released (agent)',
            'admin_release' => 'Hold released (admin)',
            'admin' => 'Administrator',
            default => $by ? ucfirst(str_replace('_', ' ', $by)) : '—',
        };
    }
}

if (! function_exists('bookingCancellationApiPayload')) {
    /**
     * Cancel_response envelope stores payload under api_response; older rows may be raw JSON only.
     *
     * @return array<string, mixed>|null
     */
    function bookingCancellationApiPayload(?array $cancelResponse): ?array
    {
        if ($cancelResponse === null || $cancelResponse === []) {
            return null;
        }

        return isset($cancelResponse['api_response']) && is_array($cancelResponse['api_response'])
            ? $cancelResponse['api_response']
            : $cancelResponse;
    }
}

if (! function_exists('formatFlightSegmentDate')) {
    /** Display date for flight segments, e.g. "23 June 2026". */
    function formatFlightSegmentDate($date): string
    {
        if (empty($date)) {
            return '';
        }

        return Carbon::parse($date)->format('j F Y');
    }
}

if (! function_exists('formatFlightClock')) {
    /** Normalize segment clock labels for cards and modals, e.g. "19:15" or "19:00". */
    function formatFlightClock(mixed $clock): string
    {
        $clock = trim((string) ($clock ?? ''));
        if ($clock === '' || $clock === '-') {
            return ' - ';
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $clock, $matches)) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        if (preg_match('/^(\d{1,2})$/', $clock, $matches)) {
            return sprintf('%02d:00', (int) $matches[1]);
        }

        return $clock;
    }
}

if (! function_exists('flightClockIsMorning')) {
    /** Morning departures (06:00–11:59), aligned with search time filters. */
    function flightClockIsMorning(mixed $clock): bool
    {
        $clock = trim((string) ($clock ?? ''));
        if ($clock === '' || ! preg_match('/^(\d{1,2}):/', $clock, $matches)) {
            return false;
        }

        $hour = (int) $matches[1];

        return $hour >= 6 && $hour < 12;
    }
}

if (! function_exists('flightClockIsRedEye')) {
    /** Night departures (22:00–05:59), aligned with search time filters. */
    function flightClockIsRedEye(mixed $clock): bool
    {
        $clock = trim((string) ($clock ?? ''));
        if ($clock === '' || ! preg_match('/^(\d{1,2}):/', $clock, $matches)) {
            return false;
        }

        $hour = (int) $matches[1];

        return $hour >= 22 || $hour < 6;
    }
}

if (! function_exists('flightDepartureTimeSlot')) {
    /** Departure time bucket aligned with flight search filters. */
    function flightDepartureTimeSlot(mixed $clock): ?string
    {
        $clock = trim((string) ($clock ?? ''));
        if ($clock === '' || ! preg_match('/^(\d{1,2}):/', $clock, $matches)) {
            return null;
        }

        $hour = (int) $matches[1];

        if ($hour >= 0 && $hour < 6) {
            return 'night';
        }
        if ($hour >= 6 && $hour < 12) {
            return 'morning';
        }
        if ($hour >= 12 && $hour < 18) {
            return 'afternoon';
        }

        return 'evening';
    }
}

if (! function_exists('flightAirportCityMap')) {
    /** @return array<string, string> */
    function flightAirportCityMap(): array
    {
        static $map = null;

        if ($map !== null) {
            return $map;
        }

        $path = public_path('user/mocks/airports.json');
        if (! is_readable($path)) {
            $map = [];

            return $map;
        }

        $rows = json_decode((string) file_get_contents($path), true);
        if (! is_array($rows)) {
            $map = [];

            return $map;
        }

        $map = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $city = trim((string) ($row['city'] ?? ''));
            $code = strtoupper(trim((string) ($row['code'] ?? '')));
            $cityCode = strtoupper(trim((string) ($row['cityCode'] ?? $code)));

            if ($code !== '' && $city !== '') {
                $map[$code] = $city;
            }

            if ($cityCode !== '' && $city !== '' && ! isset($map[$cityCode])) {
                $map[$cityCode] = $city;
            }
        }

        return $map;
    }
}

if (! function_exists('resolveFlightCityLabel')) {
    /**
     * Prefer a human city name; Sabre often returns IATA codes like "DXB" in the city field.
     */
    function resolveFlightCityLabel(?string $cityName, ?string $airportCode): string
    {
        $cityName = trim((string) ($cityName ?? ''));
        $airportCode = strtoupper(trim((string) ($airportCode ?? '')));
        $map = flightAirportCityMap();

        if ($airportCode !== '' && isset($map[$airportCode])) {
            return $map[$airportCode];
        }

        if ($cityName !== '' && ! preg_match('/^[A-Z0-9]{3}$/', strtoupper($cityName))) {
            return $cityName;
        }

        $lookup = strtoupper($cityName !== '' ? $cityName : $airportCode);
        if ($lookup === '') {
            return '';
        }

        return $map[$lookup] ?? $lookup;
    }
}

if (! function_exists('formatFlightAircraftCode')) {
    function formatFlightAircraftCode(?string $code): string
    {
        $code = strtoupper(trim((string) ($code ?? '')));

        if ($code === '') {
            return '';
        }

        static $labels = [
            '319' => 'Airbus A319',
            '320' => 'Airbus A320',
            '321' => 'Airbus A321',
            '32A' => 'Airbus A320',
            '32B' => 'Airbus A321',
            '32N' => 'Airbus A320neo',
            '32Q' => 'Airbus A321neo',
            '330' => 'Airbus A330',
            '332' => 'Airbus A330-200',
            '333' => 'Airbus A330-300',
            '339' => 'Airbus A330-900neo',
            '350' => 'Airbus A350',
            '359' => 'Airbus A350-900',
            '351' => 'Airbus A350-1000',
            '380' => 'Airbus A380',
            '388' => 'Airbus A380',
            '737' => 'Boeing 737',
            '738' => 'Boeing 737-800',
            '739' => 'Boeing 737-900',
            '73H' => 'Boeing 737-800',
            '73J' => 'Boeing 737-900',
            '7M8' => 'Boeing 737 MAX 8',
            '7M9' => 'Boeing 737 MAX 9',
            '747' => 'Boeing 747',
            '744' => 'Boeing 747-400',
            '772' => 'Boeing 777-200',
            '773' => 'Boeing 777-300',
            '77L' => 'Boeing 777-200LR',
            '77W' => 'Boeing 777-300ER',
            '787' => 'Boeing 787',
            '788' => 'Boeing 787-8',
            '789' => 'Boeing 787-9',
            '781' => 'Boeing 787-10',
            '223' => 'Airbus A220-300',
            '221' => 'Airbus A220-100',
            'E90' => 'Embraer E190',
            'E95' => 'Embraer E195',
            '295' => 'Embraer E195-E2',
            'AT7' => 'ATR 72',
            'ATR' => 'ATR 72',
        ];

        return $labels[$code] ?? $code;
    }
}

if (! function_exists('formatFlightAircraftLabel')) {
    /**
     * @param  array<string, mixed>  $segment
     */
    function formatFlightAircraftLabel(array $segment): string
    {
        $code = trim((string) ($segment['equipment'] ?? ''));

        foreach (['equipment_type_first', 'equipment_type_last'] as $key) {
            $type = trim((string) ($segment[$key] ?? ''));

            if ($type === '') {
                continue;
            }

            if (preg_match('/^(airbus|boeing|embraer|atr|bombardier|canadair|mcdonnell)/i', $type)) {
                return $type;
            }

            if (strlen($type) > 3 && ! preg_match('/^[A-Z0-9]{3}$/', strtoupper($type))) {
                return $type;
            }
        }

        return formatFlightAircraftCode($code);
    }
}

if (! function_exists('compactFlightBaggagePillText')) {
    function compactFlightBaggagePillText(?string $text): string
    {
        $text = trim((string) ($text ?? ''));
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/\s+checked\b/i', '', $text);
        $text = preg_replace('/\s+cabin\b/i', '', $text);

        return trim(preg_replace('/\s{2,}/', ' ', $text));
    }
}

if (! function_exists('flightFareBaggagePillFromRow')) {
    /**
     * @param  array<string, mixed>  $row
     */
    function flightFareBaggagePillFromRow(array $row, bool $isCabin): ?string
    {
        $amount = trim((string) data_get($row, 'friendly.amount', ''));

        if ($amount === '' || strcasecmp($amount, 'Not included') === 0) {
            $amount = trim((string) ($row['allowance'] ?? ''));
        }

        if ($amount === '' || strcasecmp($amount, 'Not included') === 0) {
            return null;
        }

        return compactFlightBaggagePillText($amount);
    }
}

if (! function_exists('flightFareBaggageDisplayLines')) {
    /**
     * Group compact baggage pills by outbound / return leg.
     *
     * @param  array<string, mixed>  $baggageDetails
     * @return array{outbound: list<string>, return: list<string>}
     */
    function flightFareBaggageDisplayLines(array $baggageDetails, bool $isRoundTrip, ?string $from, ?string $to): array
    {
        $from = strtoupper(trim((string) ($from ?? '')));
        $to = strtoupper(trim((string) ($to ?? '')));
        $legPills = [0 => [], 1 => []];

        foreach (['checked', 'cabin'] as $section) {
            $isCabin = $section === 'cabin';

            foreach ($baggageDetails[$section] ?? [] as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $pill = flightFareBaggagePillFromRow($row, $isCabin);
                if ($pill === null || $pill === '') {
                    continue;
                }

                $route = strtoupper(trim((string) ($row['route'] ?? '')));
                $leg = 0;

                if ($isRoundTrip && $to !== '' && preg_match('/^' . preg_quote($to, '/') . '\s*→/', $route)) {
                    $leg = 1;
                } elseif ($from !== '' && preg_match('/^' . preg_quote($from, '/') . '\s*→/', $route)) {
                    $leg = 0;
                }

                if (! in_array($pill, $legPills[$leg], true)) {
                    $legPills[$leg][] = $pill;
                }
            }
        }

        if ($legPills[0] === [] && $legPills[1] === []) {
            $compact = [];

            foreach ($baggageDetails['summary_items'] ?? [] as $item) {
                $pill = compactFlightBaggagePillText((string) $item);
                if ($pill !== '' && ! in_array($pill, $compact, true)) {
                    $compact[] = $pill;
                }
            }

            return ['outbound' => $compact, 'return' => []];
        }

        if (! $isRoundTrip) {
            $merged = array_values(array_unique(array_merge($legPills[0], $legPills[1])));

            return ['outbound' => $merged, 'return' => []];
        }

        return [
            'outbound' => $legPills[0],
            'return' => $legPills[1],
        ];
    }
}

if (! function_exists('flightLegSegmentRoutes')) {
    /**
     * @param  array<string, mixed>  $leg
     * @return list<string>
     */
    function flightLegSegmentRoutes(array $leg): array
    {
        $routes = [];

        foreach ($leg['segments'] ?? [] as $segment) {
            if (! is_array($segment)) {
                continue;
            }

            $from = strtoupper(trim((string) ($segment['from'] ?? '')));
            $to = strtoupper(trim((string) ($segment['to'] ?? '')));

            if ($from !== '' && $to !== '') {
                $routes[] = $from . ' → ' . $to;
            }
        }

        return array_values(array_unique($routes));
    }
}

if (! function_exists('flightLegEndpointRoute')) {
    /**
     * @param  array<string, mixed>  $leg
     */
    function flightLegEndpointRoute(array $leg): ?string
    {
        $segments = array_values(array_filter($leg['segments'] ?? [], 'is_array'));

        if ($segments === []) {
            return null;
        }

        $first = $segments[0];
        $last = $segments[array_key_last($segments)];
        $from = strtoupper(trim((string) ($first['from'] ?? '')));
        $to = strtoupper(trim((string) ($last['to'] ?? '')));

        if ($from === '' || $to === '') {
            return null;
        }

        return $from . ' → ' . $to;
    }
}

if (! function_exists('flightBaggageRowMatchesRoutes')) {
    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $routes
     */
    function flightBaggageRowMatchesRoutes(array $row, array $routes): bool
    {
        $route = strtoupper(trim((string) ($row['route'] ?? '')));

        if ($route === '' || strcasecmp($route, 'All segments') === 0) {
            return false;
        }

        foreach ($routes as $matchRoute) {
            if ($route === strtoupper(trim($matchRoute))) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('flightFareBaggagePillsForLeg')) {
    /**
     * Resolve compact baggage pills for one flight leg from Sabre route rows.
     *
     * @param  array<string, mixed>  $baggageDetails
     * @param  array<string, mixed>  $leg
     * @return list<string>
     */
    function flightFareBaggagePillsForLeg(array $baggageDetails, array $leg, bool $isReturnLeg = false): array
    {
        $routes = flightLegSegmentRoutes($leg);
        $endpoint = flightLegEndpointRoute($leg);

        if ($endpoint !== null && ! in_array($endpoint, $routes, true)) {
            $routes[] = $endpoint;
        }

        if ($routes === []) {
            return [];
        }

        $pills = [];

        foreach (['checked', 'cabin'] as $section) {
            $isCabin = $section === 'cabin';

            foreach ($baggageDetails[$section] ?? [] as $row) {
                if (! is_array($row)) {
                    continue;
                }

                if ($isReturnLeg && ! empty($row['inferred_allowance'])) {
                    continue;
                }

                if (! flightBaggageRowMatchesRoutes($row, $routes)) {
                    continue;
                }

                $pill = flightFareBaggagePillFromRow($row, $isCabin);

                if ($pill !== null && $pill !== '' && ! in_array($pill, $pills, true)) {
                    $pills[] = $pill;
                }
            }
        }

        return $pills;
    }
}

if (! function_exists('flightFareBaggagePillsFromNote')) {
    /**
     * @return list<string>
     */
    function flightFareBaggagePillsFromNote(?string $bagNote): array
    {
        $bagNote = trim((string) ($bagNote ?? ''));

        if ($bagNote === '') {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($item) => compactFlightBaggagePillText((string) $item),
            preg_split('/\s*·\s*/', $bagNote) ?: [$bagNote],
        ))));
    }
}

if (! function_exists('flightFareLegBookingCode')) {
    /**
     * @param  array<string, mixed>|null  $component
     * @param  array<string, mixed>  $firstSeg
     */
    function flightFareLegBookingCode(?array $component, array $firstSeg, ?string $fareFallback): string
    {
        $basis = trim((string) ($component['fare_basis'] ?? ''));
        if ($basis !== '' && preg_match('/^[A-Z]/i', $basis)) {
            return strtoupper($basis[0]);
        }

        $fromSeg = strtoupper(trim((string) ($firstSeg['booking_code'] ?? '')));
        if ($fromSeg !== '') {
            return $fromSeg;
        }

        return strtoupper(trim((string) ($fareFallback ?? '')));
    }
}

if (! function_exists('flightFareLegSeats')) {
    /**
     * @param  array<string, mixed>  $leg
     */
    function flightFareLegSeats(array $leg): ?int
    {
        $counts = [];

        foreach ($leg['segments'] ?? [] as $seg) {
            if (! is_array($seg)) {
                continue;
            }

            if (isset($seg['seats_available']) && is_numeric($seg['seats_available'])) {
                $counts[] = (int) $seg['seats_available'];
            }
        }

        return $counts !== [] ? min($counts) : null;
    }
}

if (! function_exists('flightFareRulesComponentForLeg')) {
    /**
     * @param  array<string, mixed>  $fareRules
     * @return array<string, mixed>|null
     */
    function flightFareRulesComponentForLeg(array $fareRules, int $legIndex, string $from, string $to): ?array
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));
        $origin = $legIndex === 0 ? $from : $to;
        $destination = $legIndex === 0 ? $to : $from;
        $expected = $origin . ' → ' . $destination;
        $components = $fareRules['components'] ?? [];

        foreach ($components as $component) {
            if (! is_array($component)) {
                continue;
            }

            if (strtoupper(trim((string) ($component['route'] ?? ''))) === $expected) {
                return $component;
            }
        }

        $fallback = $components[$legIndex] ?? null;

        return is_array($fallback) ? $fallback : null;
    }
}

if (! function_exists('flightFareChannel')) {
    /**
     * @param  array<string, mixed>  $fare
     */
    function flightFareChannel(array $fare): string
    {
        $tags = array_map(
            static fn ($tag) => strtolower(trim((string) $tag)),
            is_array($fare['fare_tags'] ?? null) ? $fare['fare_tags'] : [],
        );

        return in_array('ndc', $tags, true) ? 'ndc' : 'gds';
    }
}

if (! function_exists('flightFareBasisListingLabel')) {
    /**
     * @param  list<string>  $fareTags
     */
    function flightFareBasisListingLabel(?string $basis, array $fareTags = []): string
    {
        $basis = trim((string) $basis);
        if ($basis === '') {
            return '';
        }

        $normalizedTags = array_map(static fn ($tag) => strtolower(trim((string) $tag)), $fareTags);

        if (in_array('ndc', $normalizedTags, true) && ! str_contains(strtoupper($basis), '/NDC')) {
            return $basis . '/NDC';
        }

        if (in_array('gds', $normalizedTags, true) && ! str_contains(strtoupper($basis), '/GDS')) {
            return $basis . '/GDS';
        }

        return $basis;
    }
}

if (! function_exists('flightFareLegDisplayRows')) {
    /**
     * @param  array<string, mixed>  $fare
     * @param  list<array<string, mixed>>  $legs
     * @return list<array{
     *     tag: string,
     *     tag_title: string,
     *     brand: string,
     *     basis: string,
     *     cabin: string,
     *     booking: string,
     *     bag_pills: list<string>,
     *     seats: ?int,
     *     non_refundable: bool
     * }>
     */
    function flightFareLegDisplayRows(
        array $fare,
        array $legs,
        bool $isRoundTrip,
        ?string $from,
        ?string $to,
        bool $nonRefundable,
        ?string $bagNoteFallback = null,
    ): array {
        $from = strtoupper(trim((string) ($from ?? '')));
        $to = strtoupper(trim((string) ($to ?? '')));
        $fareRules = is_array($fare['fare_rules'] ?? null) ? $fare['fare_rules'] : [];
        $baggageDetails = is_array($fare['baggage_details'] ?? null) ? $fare['baggage_details'] : [];
        $fareBrand = trim((string) ($fare['fare_brand'] ?? ''));
        $fareBasis = trim((string) ($fare['fare_basis'] ?? ''));
        $fareCabin = trim((string) ($fare['cabin_code'] ?? ''));
        $fareBooking = trim((string) ($fare['booking_code'] ?? ''));
        $fareSeats = isset($fare['seats_available']) && is_numeric($fare['seats_available'])
            ? (int) $fare['seats_available']
            : null;
        $fareTags = is_array($fare['fare_tags'] ?? null) ? $fare['fare_tags'] : [];
        $summaryPills = [];

        foreach ($baggageDetails['summary_items'] ?? [] as $item) {
            $pill = compactFlightBaggagePillText((string) $item);

            if ($pill !== '' && ! in_array($pill, $summaryPills, true)) {
                $summaryPills[] = $pill;
            }
        }

        $buildRow = function (int $legIndex, string $tag, string $tagTitle) use (
            $legs,
            $fareRules,
            $baggageDetails,
            $from,
            $to,
            $fareBrand,
            $fareBasis,
            $fareCabin,
            $fareBooking,
            $fareSeats,
            $nonRefundable,
            $isRoundTrip,
            $bagNoteFallback,
            $summaryPills,
            $fareTags,
        ): array {
            $leg = $legs[$legIndex] ?? [];
            $isReturnLeg = $isRoundTrip && $legIndex > 0;
            $useFareFallback = ! $isReturnLeg;
            $firstSeg = ($leg['segments'] ?? [])[0] ?? [];

            if (! is_array($firstSeg)) {
                $firstSeg = [];
            }

            $component = flightFareRulesComponentForLeg($fareRules, $legIndex, $from, $to);
            $brand = trim((string) ($component['brand'] ?? ''));

            if ($brand === '' && $useFareFallback) {
                $brand = $fareBrand;
            }

            $basis = trim((string) ($component['fare_basis'] ?? ''));

            if ($basis === '' && $useFareFallback) {
                $basis = $fareBasis;
            }

            $cabin = trim((string) ($component['cabin'] ?? ''));

            if ($cabin === '') {
                $cabin = trim((string) ($firstSeg['cabin_code'] ?? ''));
            }

            if ($cabin === '' && $useFareFallback) {
                $cabin = $fareCabin;
            }

            $booking = flightFareLegBookingCode(
                $component,
                $firstSeg,
                $useFareFallback ? $fareBooking : '',
            );
            $seats = flightFareLegSeats($leg);

            if ($seats === null && $useFareFallback) {
                $seats = $fareSeats;
            }

            $bagPills = flightFareBaggagePillsForLeg($baggageDetails, $leg, $isReturnLeg);

            if ($bagPills === [] && $useFareFallback) {
                if ($summaryPills !== []) {
                    $bagPills = $summaryPills;
                } else {
                    $bagPills = flightFareBaggagePillsFromNote($bagNoteFallback);
                }
            }

            return [
                'tag' => $tag,
                'tag_title' => $tagTitle,
                'brand' => $brand,
                'basis' => flightFareBasisListingLabel($basis, $fareTags),
                'cabin' => $cabin,
                'booking' => $booking,
                'bag_pills' => $bagPills,
                'seats' => $seats,
                'non_refundable' => $nonRefundable,
            ];
        };

        if (! $isRoundTrip) {
            return [$buildRow(0, '', '')];
        }

        return [
            $buildRow(0, 'OW', 'Outbound'),
            $buildRow(1, 'RT', 'Return'),
        ];
    }
}

if (! function_exists('formatDateTime')) {
    function formatDateTime($date)
    {
        if (empty($date)) {
            return '-';
        }

        return \Carbon\Carbon::parse($date)->format('M j, Y - g:i A');
    }
}
if (! function_exists('formatDate')) {
    function formatDate($date)
    {
        return Carbon::parse($date)->format('M j, Y');
    }
}
if (! function_exists('formatKey')) {
    function formatKey($value)
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}
if (! function_exists('sanitizeBulletText')) {
    function sanitizeBulletText(string $text)
    {
        // Decode any HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Normalize bullet symbols to a single delimiter
        $text = preg_replace('/[•●▪◦]/u', '||', $text);

        // Normalize line breaks
        $text = preg_replace("/\r\n|\r|\n/", '||', $text);

        // Split into items
        $items = array_filter(array_map('trim', explode('||', $text)));

        // Build HTML list
        $html = '<ul class="tour-terms">';
        foreach ($items as $item) {
            $html .= '<li>' . e($item) . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }
}

if (! function_exists('vendorPricing')) {
    function vendorPricing(): \App\Services\VendorPricingService
    {
        return app(\App\Services\VendorPricingService::class);
    }
}

/**
 * Unified flight sell price — agency discount then agent/agency markup.
 * Pass supplier/list fare; returns the amount shown across search, checkout, and bookings.
 */
if (! function_exists('flightSellPrice')) {
    function flightSellPrice(float $amount): float
    {
        return vendorPricing()->applyFlight(auth()->user(), $amount)->sellAmount;
    }
}

/**
 * @param  list<array<string, mixed>>  $results
 * @return list<array<string, mixed>>
 */
if (! function_exists('applyFlightSearchPricing')) {
    function applyFlightSearchPricing(array $results): array
    {
        return vendorPricing()->applyFlightResults(auth()->user(), $results);
    }
}

/**
 * @param  array<string, mixed>  $itineraryData
 * @return array<string, mixed>
 */
if (! function_exists('flightBookingPricingFields')) {
    function flightBookingPricingFields(array $itineraryData, float $fallbackSellAmount): array
    {
        return vendorPricing()->bookingFieldsFromFlightItinerary($itineraryData, $fallbackSellAmount);
    }
}

/**
 * Fare breakdown for hold/checkout — base and tax from API when available.
 * Discount and markup apply to base fare only; taxes stay unchanged.
 *
 * @param  array<string, mixed>  $itinerary
 * @return array<string, mixed>
 */
if (! function_exists('flightFareBreakdown')) {
    function flightFareBreakdown(array $itinerary, float $fallbackTotal = 0): array
    {
        $currency = strtoupper((string) ($itinerary['currency'] ?? 'AED'));
        $supplierBase = round((float) ($itinerary['supplierBasePrice'] ?? 0), 2);
        $supplierTax = round((float) ($itinerary['supplierTaxes'] ?? 0), 2);
        $baseFare = round((float) ($itinerary['basePrice'] ?? 0), 2);
        $taxCharges = round((float) ($itinerary['taxes'] ?? 0), 2);
        $totalAmount = round((float) ($itinerary['totalPrice'] ?? $fallbackTotal), 2);
        $discount = round((float) ($itinerary['vendorDiscount'] ?? 0), 2);
        $markup = round((float) ($itinerary['vendorMarkup'] ?? 0), 2);

        $hasBreakdown = ($supplierBase > 0.001 || $baseFare > 0.001)
            && ($supplierTax > 0.001 || $taxCharges > 0.001);

        if ($hasBreakdown && $totalAmount <= 0) {
            $totalAmount = round(($baseFare > 0 ? $baseFare : $supplierBase) + ($taxCharges > 0 ? $taxCharges : $supplierTax), 2);
        }

        $displayBase = $baseFare > 0 ? $baseFare : $supplierBase;
        $displayTax = $taxCharges > 0 ? $taxCharges : $supplierTax;
        $netFare = round(max(0, $totalAmount - $markup), 2);

        return [
            'currency' => $currency,
            'has_breakdown' => $hasBreakdown,
            'base_fare' => $displayBase,
            'tax_charges' => $displayTax,
            'supplier_base' => $supplierBase,
            'supplier_tax' => $supplierTax,
            'discount' => $discount,
            'markup' => $markup,
            'you_earn' => $markup,
            'total_amount' => $totalAmount,
            'net_fare' => $netFare,
            'show_discount' => $discount > 0.001,
            'show_you_earn' => $markup > 0.001,
            'show_net_fare' => $markup > 0.001,
        ];
    }
}

/**
 * Unified hotel sell price — agency discount then agent/agency markup.
 * Pass list price (after any legacy commission step).
 */
if (! function_exists('hotelSellPrice')) {
    function hotelSellPrice(float $listAmount): float
    {
        return vendorPricing()->applyHotel(auth()->user(), $listAmount)->sellAmount;
    }
}

/**
 * Supplier net → legacy commission → vendor discount.
 * Commission/markup will be consolidated into hotelSellPrice() later.
 */
if (! function_exists('hotelSellPriceFromNet')) {
    function hotelSellPriceFromNet(float $netAmount, float $commissionPercentage = 10): float
    {
        $listAmount = round($netAmount + ($commissionPercentage / 100) * $netAmount, 2);

        return hotelSellPrice($listAmount);
    }
}

/**
 * Yalago board payload → legacy commission → vendor discount.
 */
if (! function_exists('hotelSellPriceFromBoard')) {
    function hotelSellPriceFromBoard(array $board, float $commissionPercent): float
    {
        if (! empty($board['IsBindingPrice'])) {
            $listAmount = round((float) $board['GrossCost']['Amount'], 2);
        } else {
            $net = (float) $board['NetCost']['Amount'];
            $listAmount = round($net + ($net * $commissionPercent) / 100, 2);
        }

        return hotelSellPrice($listAmount);
    }
}

if (! function_exists('hotelBookingPricingFields')) {
    function hotelBookingPricingFields(float $sellAmount): array
    {
        return vendorPricing()->buildBookingPricingFromSell(
            auth()->user(),
            \App\Services\VendorPricingService::PRODUCT_HOTEL,
            $sellAmount
        );
    }
}

if (! function_exists('bookingVendorAgency')) {
    /** Agency account for a booking (parent agency when booked by a sub-agent). */
    function bookingVendorAgency(?\App\Models\B2bVendor $booker): ?\App\Models\B2bVendor
    {
        if ($booker === null) {
            return null;
        }

        if ($booker->parent_vendor_id) {
            $booker->loadMissing('parentVendor');

            return $booker->parentVendor;
        }

        return $booker;
    }
}

if (! function_exists('bookingVendorIsSubAgent')) {
    function bookingVendorIsSubAgent(?\App\Models\B2bVendor $booker): bool
    {
        return $booker !== null && $booker->parent_vendor_id !== null;
    }
}
