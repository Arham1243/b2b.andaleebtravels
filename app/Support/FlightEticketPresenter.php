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
        $shared = self::sharedContext($booking, $includeFare);
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
            ...self::sharedContext($booking, $includeFare),
            'travelers' => $travelers,
            'filename_ticket_number' => $numbers[0] ?? 'eticket',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function sharedContext(B2bFlightBooking $booking, bool $includeFare): array
    {
        $itinerary = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
        $legs = is_array($itinerary['legs'] ?? null) ? $itinerary['legs'] : [];
        $baggage = is_array($itinerary['baggage_details'] ?? null) ? $itinerary['baggage_details'] : [];

        return [
            'agency' => self::agencyBlock(),
            'booking' => [
                'ref' => $booking->booking_number,
                'date' => $booking->created_at?->format('d M Y') ?? '—',
                'pnr' => strtoupper(trim((string) ($booking->sabre_record_locator ?? ''))),
                'airline_ref' => strtoupper(trim((string) ($booking->sabre_record_locator ?? ''))),
                'crs_ref' => strtoupper(trim((string) ($booking->sabre_record_locator ?? ''))),
                'view_url' => $booking->id
                    ? route('user.bookings.flights.detail', $booking->id, absolute: true)
                    : null,
            ],
            'include_fare' => $includeFare,
            'directions' => self::buildDirections($booking, $legs, $baggage, $itinerary),
            'notes' => self::buildNotes($itinerary),
        ];
    }

    /**
     * @return array{name: string, legal_name: string, address: string, phone: string, email: string, logo_data_uri: ?string}
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
            'legal_name' => (string) config('eticket.agency_legal_name', config('app.name', 'Andaleeb Travel Agency')),
            'address' => (string) config('eticket.address', ''),
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
    private static function buildDirections(B2bFlightBooking $booking, array $legs, array $baggage, array $itinerary): array
    {
        $directions = [];

        foreach ($legs as $index => $leg) {
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

            $departureDate = $index === 0
                ? $booking->departure_date
                : $booking->return_date;

            $directions[] = [
                'key' => $index === 0 ? 'onward' : 'return',
                'label' => $index === 0 ? 'ONWARD' : 'RETURN',
                'route_title' => self::routeTitle($first, $last),
                'meta_line' => self::directionMetaLine($departureDate, $stops, $durMins),
                'airline' => self::airlineLabel($first),
                'travel_class' => trim((string) ($first['cabin_code'] ?? $itinerary['cabin'] ?? 'Economy')),
                'check_in_baggage' => self::baggageLine($baggage, false),
                'cabin_baggage' => self::baggageLine($baggage, true),
                'segments' => self::mapSegments($segments),
                'baggage_notes' => self::baggageNotes($baggage),
                'first_segment' => $first,
                'departure_date' => $departureDate?->format('Y-m-d'),
            ];
        }

        return $directions;
    }

    /**
     * @param  array<string, mixed>  $first
     * @param  array<string, mixed>  $last
     */
    private static function routeTitle(array $first, array $last): string
    {
        $fromCity = trim((string) ($first['departure_city'] ?? $first['from'] ?? ''));
        $toCity = trim((string) ($last['arrival_city'] ?? $last['to'] ?? ''));

        return $fromCity . ' - ' . $toCity;
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
    private static function mapSegments(array $segments): array
    {
        $mapped = [];

        foreach ($segments as $segment) {
            if (! is_array($segment)) {
                continue;
            }

            $from = strtoupper(trim((string) ($segment['from'] ?? '')));
            $to = strtoupper(trim((string) ($segment['to'] ?? '')));
            $carrier = strtoupper(trim((string) ($segment['carrier'] ?? '')));
            $flightNumber = trim((string) ($segment['flight_number'] ?? ''));

            $mapped[] = [
                'flight_number' => trim($carrier . ' ' . $flightNumber),
                'operated_by' => trim((string) ($segment['carrier_display'] ?? $segment['carrier_name'] ?? $carrier)),
                'from_code' => $from,
                'from_airport' => trim((string) ($segment['departure_city'] ?? $segment['from_airport'] ?? '')),
                'from_country' => trim((string) ($segment['departure_country'] ?? '')),
                'from_terminal' => trim((string) ($segment['departure_terminal'] ?? '')),
                'departure_time' => trim((string) ($segment['departure_clock'] ?? '')),
                'departure_date' => trim((string) ($segment['departure_display'] ?? '')),
                'stops_label' => 'Non Stop',
                'duration_label' => trim((string) ($segment['duration_display'] ?? '')),
                'to_code' => $to,
                'to_airport' => trim((string) ($segment['arrival_city'] ?? $segment['to_airport'] ?? '')),
                'to_country' => trim((string) ($segment['arrival_country'] ?? '')),
                'to_terminal' => trim((string) ($segment['arrival_terminal'] ?? '')),
                'arrival_time' => trim((string) ($segment['arrival_clock'] ?? '')),
                'arrival_date' => trim((string) ($segment['arrival_display'] ?? '')),
                'raw' => $segment,
            ];
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $baggage
     * @return list<string>
     */
    private static function baggageNotes(array $baggage): array
    {
        $notes = [];
        $summaryItems = is_array($baggage['summary_items'] ?? null) ? $baggage['summary_items'] : [];

        foreach ($summaryItems as $item) {
            $text = trim((string) $item);
            if ($text !== '') {
                $notes[] = $text;
            }
        }

        if ($notes === []) {
            $notes[] = 'Refer to airline baggage policy for further details.';
        }

        $notes[] = 'Bag 1 Chgs May Apply if Bags Exceed Ttl Wt Allowance';
        $notes[] = 'Bag 2 Chgs May Apply if Bags Exceed Ttl Wt Allowance';

        return array_values(array_unique($notes));
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
        $directions = self::buildDirections($booking, $legs, is_array($itinerary['baggage_details'] ?? null) ? $itinerary['baggage_details'] : [], $itinerary);

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
            foreach ($directions as $direction) {
                $segment = is_array($direction['first_segment'] ?? null) ? $direction['first_segment'] : [];
                $directionBarcodes[$direction['key']] = FlightEticketBarcodeGenerator::pngBase64(
                    $name,
                    $pnr,
                    $segment,
                    $direction['departure_date'] ?? null,
                );
            }

            $travelers[] = [
                'name' => $name,
                'ticket_number' => $ticketNumber,
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
}
