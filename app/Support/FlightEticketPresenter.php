<?php

namespace App\Support;

use App\Models\B2bFlightBooking;
use Carbon\Carbon;

final class FlightEticketPresenter
{
    /**
     * @param  array{source: ?string, error: ?string, tickets: list<array<string, mixed>>}  $ticketDetails
     * @return list<array<string, mixed>>
     */
    public static function separate(B2bFlightBooking $booking, array $ticketDetails, bool $includeFare = true): array
    {
        $shared = self::sharedContext($booking, $includeFare, $ticketDetails);
        $travelers = self::buildTravelers($booking, $ticketDetails);

        if ($travelers === []) {
            return [];
        }

        return array_map(function (array $traveler) use ($shared) {
            return [
                ...$shared,
                'travelers' => [$traveler],
                'filename_ticket_number' => $traveler['ticket_number'] ?? 'eticket',
            ];
        }, $travelers);
    }

    /**
     * @param  array{source: ?string, error: ?string, tickets: list<array<string, mixed>>}  $ticketDetails
     * @return array<string, mixed>|null
     */
    public static function combined(B2bFlightBooking $booking, array $ticketDetails, bool $includeFare = true): ?array
    {
        $travelers = self::buildTravelers($booking, $ticketDetails);
        if ($travelers === []) {
            return null;
        }

        $numbers = array_values(array_filter(array_map(
            fn (array $t) => (string) ($t['ticket_number'] ?? ''),
            $travelers,
        )));

        return [
            ...self::sharedContext($booking, $includeFare, $ticketDetails),
            'travelers' => $travelers,
            'filename_ticket_number' => $numbers[0] ?? 'eticket',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param  array{source: ?string, error: ?string, tickets: list<array<string, mixed>>}  $ticketDetails
     */
    private static function sharedContext(B2bFlightBooking $booking, bool $includeFare, array $ticketDetails = []): array
    {
        $itinerary = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
        $legs = is_array($itinerary['legs'] ?? null) ? $itinerary['legs'] : [];
        $baggage = is_array($itinerary['baggage_details'] ?? null) ? $itinerary['baggage_details'] : [];
        $coupons = self::referenceCoupons(is_array($ticketDetails['tickets'] ?? null) ? $ticketDetails['tickets'] : []);

        $directions = self::buildDirections($booking, $legs, $baggage, $itinerary, $coupons);

        return [
            'agency' => self::agencyBlock(),
            'booking' => [
                'ref' => $booking->booking_number,
                'date' => $booking->created_at?->format('d M Y') ?? '—',
                'pnr' => strtoupper(trim((string) ($booking->sabre_record_locator ?? ''))),
                'airline_ref' => strtoupper(trim((string) ($booking->sabre_record_locator ?? ''))),
                'crs_ref' => strtoupper(trim((string) ($booking->sabre_record_locator ?? ''))),
            ],
            'include_fare' => $includeFare,
            'directions' => $directions,
            'baggage' => self::sharedBaggageBlock($baggage),
            'notes' => self::buildNotes($itinerary),
        ];
    }

    /**
     * @return array{name: string, legal_name: string, address: string, country: string, phone: string, email: string, logo_data_uri: ?string}
     */
    private static function agencyBlock(): array
    {
        $logoPath = (string) config('eticket.logo_path', '');
        $logoDataUri = null;

        if ($logoPath !== '' && is_file($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($logoPath));
        }

        return [
            'name' => (string) config('eticket.agency_name', config('app.name', 'Andaleeb Travel Agency')),
            'legal_name' => self::titleCaseName((string) config('eticket.agency_legal_name', config('app.name', 'Andaleeb Travel Agency'))),
            'address' => self::titleCaseName((string) config('eticket.address', '')),
            'country' => (string) config('eticket.country', 'United Arab Emirates'),
            'phone' => (string) config('eticket.phone', ''),
            'email' => (string) config('eticket.email', ''),
            'logo_data_uri' => $logoDataUri,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $legs
     * @param  array<string, mixed>  $baggage
     * @param  array<string, mixed>  $itinerary
     * @return list<array<string, mixed>>
     */
    /**
     * @param  list<array<string, mixed>>  $coupons
     */
    private static function buildDirections(
        B2bFlightBooking $booking,
        array $legs,
        array $baggage,
        array $itinerary,
        array $coupons = [],
    ): array {
        $directions = [];

        foreach (FlightItineraryLegsNormalizer::normalize($legs, $booking, $coupons) as $index => $leg) {
            if (! is_array($leg)) {
                continue;
            }

            $segments = is_array($leg['segments'] ?? null) ? $leg['segments'] : [];
            if ($segments === []) {
                continue;
            }

            $first = $segments[0];
            $last = $segments[array_key_last($segments)];
            $stops = max(0, count($segments) - 1);
            $durMins = (int) ($leg['elapsedTime'] ?? 0);

            $departureDate = self::resolveDirectionDepartureDate($booking, $index, $first);

            $directions[] = [
                'key' => $index === 0 ? 'onward' : 'return',
                'label' => $index === 0 ? 'ONWARD' : 'RETURN',
                'route_title' => self::routeTitle($first, $last),
                'meta_line' => self::directionMetaLine($departureDate, $stops, $durMins),
                'airline_ref' => strtoupper(trim((string) ($booking->sabre_record_locator ?? ''))),
                'crs_ref' => strtoupper(trim((string) ($booking->sabre_record_locator ?? ''))),
                'airline' => self::airlineLabel($first),
                'travel_class' => trim((string) ($first['cabin_code'] ?? $itinerary['cabin'] ?? 'Economy')),
                'check_in_baggage' => self::baggageLine($baggage, false),
                'cabin_baggage' => self::baggageLine($baggage, true),
                'segments' => self::mapSegments($segments, $durMins),
                'first_segment' => $first,
                'departure_date' => self::segmentDepartureIso($first) ?? $departureDate?->format('Y-m-d'),
            ];
        }

        return $directions;
    }

    /**
     * @param  array<string, mixed>  $firstSegment
     */
    private static function resolveDirectionDepartureDate(B2bFlightBooking $booking, int $index, array $firstSegment): ?Carbon
    {
        $segmentDate = self::segmentDepartureCarbon($firstSegment);
        if ($segmentDate !== null) {
            return $segmentDate;
        }

        $bookingDate = $index === 0 ? $booking->departure_date : $booking->return_date;

        return $bookingDate instanceof Carbon ? $bookingDate : null;
    }

    /**
     * @param  array<string, mixed>  $segment
     */
    private static function segmentDepartureCarbon(array $segment): ?Carbon
    {
        foreach ([
            $segment['departure_datetime'] ?? null,
            $segment['departure_at'] ?? null,
            $segment['departure'] ?? null,
            $segment['departure_iso'] ?? null,
        ] as $candidate) {
            $text = trim((string) $candidate);
            if ($text === '') {
                continue;
            }

            try {
                return Carbon::parse($text);
            } catch (\Throwable) {
                continue;
            }
        }

        foreach ([
            $segment['departure_display'] ?? null,
            $segment['departure_label'] ?? null,
        ] as $candidate) {
            $text = trim((string) $candidate);
            if ($text === '') {
                continue;
            }

            try {
                return Carbon::parse($text);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $segment
     */
    private static function segmentDepartureIso(array $segment): ?string
    {
        return self::segmentDepartureCarbon($segment)?->format('Y-m-d');
    }

    /**
     * @param  array<string, mixed>  $baggage
     * @return array{cabin_baggage: string, check_in_baggage: string, notes: list<string>}
     */
    private static function sharedBaggageBlock(array $baggage): array
    {
        return [
            'cabin_baggage' => self::baggageLine($baggage, true),
            'check_in_baggage' => self::baggageLine($baggage, false),
            'notes' => self::baggageNotes($baggage),
        ];
    }

    /**
     * @param  array<string, mixed>  $first
     * @param  array<string, mixed>  $last
     */
    private static function routeTitle(array $first, array $last): string
    {
        $fromCity = trim((string) ($first['departure_city'] ?? $first['from'] ?? ''));
        $toCity = trim((string) ($last['arrival_city'] ?? $last['to'] ?? ''));

        return $fromCity . ' → ' . $toCity;
    }

    private static function directionMetaLine(?Carbon $date, int $stops, int $durMins): string
    {
        $parts = [];
        if ($date) {
            $parts[] = $date->format('d M Y');
        }

        $parts[] = $stops === 0 ? 'Non Stop' : ($stops . ' Stop' . ($stops > 1 ? 's' : ''));

        if ($durMins > 0) {
            $h = intdiv($durMins, 60);
            $m = $durMins % 60;
            $parts[] = $h > 0
                ? trim(($h . ' hrs') . ($m > 0 ? ' ' . $m . ' mins' : ''))
                : ($m . ' mins');
        }

        return implode(' | ', $parts);
    }

    /**
     * @param  array<string, mixed>  $segment
     */
    private static function airlineLabel(array $segment): string
    {
        $display = trim((string) ($segment['carrier_display'] ?? ''));
        if ($display !== '') {
            return $display;
        }

        $carrier = strtoupper(trim((string) ($segment['carrier'] ?? '')));
        $flight = trim((string) ($segment['flight_number'] ?? ''));

        return trim($carrier . ($flight !== '' ? ' ' . $flight : ''));
    }

    /**
     * @param  array<string, mixed>  $baggage
     */
    private static function baggageLine(array $baggage, bool $cabin): string
    {
        $table = is_array($baggage['pax_table'] ?? null) ? $baggage['pax_table'] : [];
        $row = is_array($table[0] ?? null) ? $table[0] : [];
        $key = $cabin ? 'cabin' : 'checked';
        $value = trim((string) ($row[$key] ?? ''));

        if ($value !== '' && strcasecmp($value, 'Not included') !== 0) {
            $pax = trim((string) ($row['pax_type'] ?? 'Adult'));

            return $pax . ' - ' . $value;
        }

        $summaryItems = is_array($baggage['summary_items'] ?? null) ? $baggage['summary_items'] : [];
        foreach ($summaryItems as $item) {
            $text = trim((string) $item);
            if ($text === '') {
                continue;
            }

            $isCabinItem = stripos($text, 'cabin') !== false || stripos($text, 'carry') !== false;
            if ($cabin === $isCabinItem) {
                return $text;
            }
        }

        return $cabin ? 'Refer to airline policy' : 'Refer to airline policy';
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     * @return list<array<string, mixed>>
     */
    private static function mapSegments(array $segments, int $legElapsedMins = 0): array
    {
        $mapped = [];
        $segmentCount = count($segments);

        foreach ($segments as $segment) {
            if (! is_array($segment)) {
                continue;
            }

            $from = strtoupper(trim((string) ($segment['from'] ?? '')));
            $to = strtoupper(trim((string) ($segment['to'] ?? '')));
            $carrier = strtoupper(trim((string) ($segment['carrier'] ?? '')));
            $flightNumber = trim((string) ($segment['flight_number'] ?? ''));

            $fromCity = trim((string) ($segment['departure_city'] ?? ''));
            if ($fromCity === '' && $from !== '') {
                $fromCity = resolveFlightCityLabel('', $from);
            }

            $toCity = trim((string) ($segment['arrival_city'] ?? ''));
            if ($toCity === '' && $to !== '') {
                $toCity = resolveFlightCityLabel('', $to);
            }

            $durationMins = self::segmentDurationMinutes($segment);
            if ($durationMins <= 0 && $segmentCount === 1 && $legElapsedMins > 0) {
                $durationMins = $legElapsedMins;
            }

            $durationLabel = self::formatDurationCompact($durationMins);
            $stopsLabel = 'Non Stop';
            $stopsDisplay = $durationLabel !== null
                ? $stopsLabel . ' , (' . $durationLabel . ')'
                : $stopsLabel;

            $operatingName = trim((string) (
                $segment['operating_carrier_name']
                ?? $segment['carrier_name']
                ?? $segment['carrier_display']
                ?? $carrier
            ));

            $mapped[] = [
                'flight_number' => trim($carrier . ' ' . $flightNumber),
                'operated_by' => $operatingName,
                'from_code' => self::locationLabel($fromCity, $from),
                'from_airport' => self::airportDetailLine($fromCity, $from, $segment['departure_country'] ?? null),
                'from_terminal' => trim((string) ($segment['departure_terminal'] ?? '')),
                'departure_time' => formatFlightClock($segment['departure_clock'] ?? ''),
                'departure_date' => self::segmentDateLabel($segment, 'departure'),
                'stops_label' => $stopsLabel,
                'duration_label' => $durationLabel,
                'stops_display' => $stopsDisplay,
                'to_code' => self::locationLabel($toCity, $to),
                'to_airport' => self::airportDetailLine($toCity, $to, $segment['arrival_country'] ?? null),
                'to_terminal' => trim((string) ($segment['arrival_terminal'] ?? '')),
                'arrival_time' => formatFlightClock($segment['arrival_clock'] ?? ''),
                'arrival_date' => self::segmentDateLabel($segment, 'arrival'),
                'raw' => $segment,
            ];
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $segment
     */
    private static function segmentDurationMinutes(array $segment): int
    {
        $elapsed = (int) ($segment['elapsedTime'] ?? 0);
        if ($elapsed > 0) {
            return $elapsed;
        }

        $display = trim((string) ($segment['duration_display'] ?? ''));
        if ($display !== '' && preg_match('/(\d+)\s*h(?:\s*(\d+)\s*m)?/i', $display, $matches)) {
            return ((int) ($matches[1] ?? 0) * 60) + (int) ($matches[2] ?? 0);
        }

        try {
            $departure = Carbon::parse((string) ($segment['departure_datetime'] ?? ''));
            $arrival = Carbon::parse((string) ($segment['arrival_datetime'] ?? ''));
            $minutes = (int) $departure->diffInMinutes($arrival, false);

            return max(0, $minutes);
        } catch (\Throwable) {
            return 0;
        }
    }

    private static function formatDurationCompact(int $minutes): ?string
    {
        if ($minutes < 1) {
            return null;
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return sprintf('%02dh:%02dm', $hours, $mins);
    }

    private static function locationLabel(string $city, string $code): string
    {
        if ($city !== '' && $code !== '') {
            return $city . ' [' . $code . ']';
        }

        return $code !== '' ? $code : $city;
    }

    private static function airportDetailLine(string $city, string $code, mixed $country): string
    {
        $cityLabel = $city !== '' ? $city : resolveFlightCityLabel('', $code);
        $parts = array_values(array_filter([$cityLabel, trim((string) ($country ?? ''))]));

        return $parts !== [] ? implode(', ', $parts) : '';
    }

    /**
     * @param  array<string, mixed>  $segment
     */
    private static function segmentDateLabel(array $segment, string $direction): string
    {
        $displayKey = $direction === 'departure' ? 'departure_display' : 'arrival_display';
        $labelKey = $direction === 'departure' ? 'departure_label' : 'arrival_label';
        $weekdayKey = $direction === 'departure' ? 'departure_weekday' : 'arrival_weekday';
        $datetimeKey = $direction === 'departure' ? 'departure_datetime' : 'arrival_datetime';

        $label = trim((string) ($segment[$displayKey] ?? $segment[$labelKey] ?? ''));
        if ($label !== '') {
            return $label;
        }

        try {
            $date = Carbon::parse((string) ($segment[$datetimeKey] ?? ''));

            return $date->format('D, d M y');
        } catch (\Throwable) {
            $weekday = trim((string) ($segment[$weekdayKey] ?? ''));

            return $weekday;
        }
    }

    /**
     * @param  array<string, mixed>  $baggage
     * @return list<string>
     */
    private static function baggageNotes(array $baggage): array
    {
        $notes = [];
        $checkIn = strtolower(self::baggageLine($baggage, false));
        $cabin = strtolower(self::baggageLine($baggage, true));
        $summaryItems = is_array($baggage['summary_items'] ?? null) ? $baggage['summary_items'] : [];

        foreach ($summaryItems as $item) {
            $text = trim((string) $item);
            if ($text === '' || self::baggageNoteDuplicatesStructuredLine($text, $checkIn, $cabin)) {
                continue;
            }

            $notes[] = $text;
        }

        if ($notes === []) {
            $notes[] = 'Refer to airline baggage policy for further details.';
        }

        $notes[] = 'Bag 1 Chgs May Apply if Bags Exceed Ttl Wt Allowance';
        $notes[] = 'Bag 2 Chgs May Apply if Bags Exceed Ttl Wt Allowance';

        return array_values(array_unique($notes));
    }

    private static function baggageNoteDuplicatesStructuredLine(string $note, string $checkIn, string $cabin): bool
    {
        $normalized = strtolower($note);

        foreach ([$checkIn, $cabin] as $structured) {
            if ($structured === '' || $structured === 'refer to airline policy') {
                continue;
            }

            if ($normalized === $structured || str_contains($normalized, $structured) || str_contains($structured, $normalized)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{source: ?string, error: ?string, tickets: list<array<string, mixed>>}  $ticketDetails
     * @return list<array<string, mixed>>
     */
    private static function buildTravelers(B2bFlightBooking $booking, array $ticketDetails): array
    {
        $tickets = is_array($ticketDetails['tickets'] ?? null) ? $ticketDetails['tickets'] : [];
        if ($tickets === []) {
            return [];
        }

        $passengers = is_array($booking->passengers_data['passengers'] ?? null)
            ? $booking->passengers_data['passengers']
            : [];
        $pnr = strtoupper(trim((string) ($booking->sabre_record_locator ?? '')));
        $itinerary = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
        $legs = is_array($itinerary['legs'] ?? null) ? $itinerary['legs'] : [];
        $baggage = is_array($itinerary['baggage_details'] ?? null) ? $itinerary['baggage_details'] : [];
        $referenceCoupons = self::referenceCoupons($tickets);
        $directions = self::buildDirections($booking, $legs, $baggage, $itinerary, $referenceCoupons);

        $travelers = [];

        foreach ($tickets as $index => $ticket) {
            if (! is_array($ticket)) {
                continue;
            }

            $pax = is_array($passengers[$index] ?? null) ? $passengers[$index] : [];
            $name = self::displayPassengerName($ticket, $pax);
            $ticketNumber = preg_replace('/\D+/', '', (string) ($ticket['ticket_number'] ?? ''))
                ?: trim((string) ($ticket['ticket_number'] ?? ''));

            if ($ticketNumber === '') {
                continue;
            }

            $directionBarcodes = [];
            foreach ($directions as $directionIndex => $direction) {
                $coupon = is_array($referenceCoupons[$directionIndex] ?? null) ? $referenceCoupons[$directionIndex] : null;
                $segment = self::segmentForBarcode($direction, $coupon);
                $directionBarcodes[$direction['key']] = FlightEticketBarcodeGenerator::pngBase64(
                    $name,
                    $pnr,
                    $segment,
                    $direction['departure_date'] ?? self::segmentDepartureIso($segment),
                );
            }

            $travelers[] = [
                'name' => $name,
                'ticket_number' => $ticketNumber,
                'passport' => self::formatPassportLabel($pax),
                'direction_barcodes' => $directionBarcodes,
                'fare' => [
                    'total' => $ticket['total_price'] ?? null,
                    'base' => $ticket['base_price'] ?? null,
                    'taxes' => $ticket['taxes'] ?? null,
                    'fare_basis' => $ticket['fare_basis'] ?? null,
                    'refundable' => $ticket['refundable'] ?? null,
                ],
            ];
        }

        return $travelers;
    }

    /**
     * @param  array<string, mixed>  $ticket
     * @param  array<string, mixed>  $pax
     */
    /**
     * @param  list<array<string, mixed>>  $tickets
     * @return list<array<string, mixed>>
     */
    private static function referenceCoupons(array $tickets): array
    {
        foreach ($tickets as $ticket) {
            if (! is_array($ticket)) {
                continue;
            }

            $coupons = is_array($ticket['coupons'] ?? null) ? $ticket['coupons'] : [];
            if ($coupons !== []) {
                return $coupons;
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $direction
     * @param  array<string, mixed>|null  $coupon
     * @return array<string, mixed>
     */
    private static function segmentForBarcode(array $direction, ?array $coupon): array
    {
        $fallback = is_array($direction['first_segment'] ?? null) ? $direction['first_segment'] : [];

        if (! is_array($coupon) || trim((string) ($coupon['route'] ?? '')) === '') {
            return $fallback;
        }

        [$from, $to] = self::parseCouponRoute((string) $coupon['route']);
        $flight = trim((string) ($coupon['flight'] ?? ''));
        $carrier = '';
        $flightNumber = '';

        if ($flight !== '' && preg_match('/^([A-Z0-9]{2,3})\s*(.*)$/i', $flight, $matches)) {
            $carrier = strtoupper($matches[1]);
            $flightNumber = trim($matches[2]);
        }

        if ($from === '' || $to === '' || $carrier === '') {
            return $fallback;
        }

        return [
            'from' => $from,
            'to' => $to,
            'carrier' => $carrier,
            'flight_number' => $flightNumber,
            'booking_code' => $coupon['booking_class'] ?? ($fallback['booking_code'] ?? 'Y'),
            'departure_datetime' => $coupon['departure'] ?? ($fallback['departure_datetime'] ?? null),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function parseCouponRoute(string $route): array
    {
        $route = strtoupper(trim($route));
        if ($route === '') {
            return ['', ''];
        }

        if (preg_match('/^([A-Z]{3})\s*(?:→|->|-)\s*([A-Z]{3})$/', $route, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return ['', ''];
    }

    /**
     * @param  array<string, mixed>  $pax
     */
    private static function formatPassportLabel(array $pax): ?string
    {
        $number = trim((string) ($pax['passport_no'] ?? ''));
        if ($number === '') {
            return null;
        }

        $expiry = trim((string) ($pax['passport_exp'] ?? ''));
        if ($expiry === '') {
            return strtoupper($number);
        }

        try {
            return strtoupper($number) . ' (exp ' . Carbon::parse($expiry)->format('d M Y') . ')';
        } catch (\Throwable) {
            return strtoupper($number);
        }
    }

    /**
     * @param  array<string, mixed>  $ticket
     * @param  array<string, mixed>  $pax
     */
    private static function displayPassengerName(array $ticket, array $pax): string
    {
        $fromTicket = trim((string) ($ticket['passenger_name'] ?? ''));
        if ($fromTicket !== '') {
            return strtoupper($fromTicket);
        }

        return strtoupper(trim(implode(' ', array_filter([
            $pax['title'] ?? '',
            $pax['first_name'] ?? '',
            $pax['last_name'] ?? '',
        ]))));
    }

    /**
     * @param  array<string, mixed>  $itinerary
     * @return list<string>
     */
    private static function buildNotes(array $itinerary): array
    {
        $notes = [];

        if (! empty($itinerary['non_refundable'])) {
            $notes[] = 'Refund/date change penalties up to 100% may apply.';
        }

        $fareRules = is_array($itinerary['fare_rules'] ?? null) ? $itinerary['fare_rules'] : [];
        foreach ($fareRules['notes'] ?? [] as $note) {
            $text = trim(strip_tags((string) $note));
            if ($text !== '') {
                $notes[] = $text;
            }
        }

        $notes[] = 'Important Note: Transit Visa is a mandatory requirement if there are via TWO Schengen countries or TWO stop in same countries';

        return array_values(array_unique($notes));
    }

    private static function titleCaseName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $acronyms = ['fze', 'llc', 'ltd', 'uae'];

        $words = preg_split('/\s+/', $name) ?: [];

        return implode(' ', array_map(function (string $word) use ($acronyms) {
            $lower = strtolower($word);
            if (in_array($lower, $acronyms, true)) {
                return strtoupper($lower);
            }

            return ucfirst($lower);
        }, $words));
    }
}
