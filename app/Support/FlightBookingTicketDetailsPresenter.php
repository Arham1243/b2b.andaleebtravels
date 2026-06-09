<?php

namespace App\Support;

use App\Models\B2bFlightBooking;
use App\Support\Travelport\TravelportTicketDetailsPresenter;

final class FlightBookingTicketDetailsPresenter
{
    /**
     * @param  array<string, mixed>|null  $liveFetch
     * @return array{
     *     source: ?string,
     *     error: ?string,
     *     tickets: list<array<string, mixed>>
     * }
     */
    public static function present(B2bFlightBooking $booking, ?array $liveFetch = null): array
    {
        if ($booking->ticket_status !== 'issued' || ! $booking->hasAirlinePnr()) {
            return [
                'source' => null,
                'error' => null,
                'tickets' => [],
            ];
        }

        if ($booking->isTravelport()) {
            return self::presentTravelport($booking, $liveFetch);
        }

        return self::presentSabre($booking, $liveFetch);
    }

    /**
     * @param  array<string, mixed>|null  $liveFetch
     * @return array{source: ?string, error: ?string, tickets: list<array<string, mixed>>}
     */
    private static function presentTravelport(B2bFlightBooking $booking, ?array $liveFetch): array
    {
        $stored = TravelportTicketDetailsPresenter::fromTicketingResponse(
            is_array($booking->ticket_response) ? $booking->ticket_response : null,
            $booking,
        );

        if (self::hasRichDetails($stored)) {
            return [
                'source' => 'saved',
                'error' => null,
                'tickets' => $stored,
            ];
        }

        if (is_array($liveFetch) && ! empty($liveFetch['tickets'])) {
            return [
                'source' => ($liveFetch['ok'] ?? false) ? 'live' : 'saved',
                'error' => ($liveFetch['ok'] ?? false) ? null : ($liveFetch['error'] ?? null),
                'tickets' => $liveFetch['tickets'],
            ];
        }

        if ($stored !== []) {
            return [
                'source' => 'saved',
                'error' => null,
                'tickets' => $stored,
            ];
        }

        return [
            'source' => 'fallback',
            'error' => is_array($liveFetch) ? ($liveFetch['error'] ?? null) : null,
            'tickets' => self::fallbackTickets($booking),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $liveFetch
     * @return array{source: ?string, error: ?string, tickets: list<array<string, mixed>>}
     */
    private static function presentSabre(B2bFlightBooking $booking, ?array $liveFetch): array
    {
        $numbers = FlightBookingTicketResolver::forBooking($booking);
        if ($numbers === []) {
            return [
                'source' => null,
                'error' => null,
                'tickets' => [],
            ];
        }

        $tickets = self::fallbackTickets($booking);

        if (is_array($liveFetch) && ($liveFetch['ok'] ?? false)) {
            return [
                'source' => 'live',
                'error' => null,
                'tickets' => $tickets,
            ];
        }

        return [
            'source' => 'saved',
            'error' => is_array($liveFetch) ? ($liveFetch['error'] ?? null) : null,
            'tickets' => $tickets,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $tickets
     */
    private static function hasRichDetails(array $tickets): bool
    {
        foreach ($tickets as $ticket) {
            if (($ticket['coupons'] ?? []) !== [] || ($ticket['passenger_name'] ?? '') !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function fallbackTickets(B2bFlightBooking $booking): array
    {
        $numbers = FlightBookingTicketResolver::forBooking($booking);
        if ($numbers === []) {
            return [];
        }

        $passengers = is_array($booking->passengers_data['passengers'] ?? null)
            ? $booking->passengers_data['passengers']
            : [];
        $itinerary = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
        $coupons = self::couponsFromItinerary($itinerary);

        $tickets = [];
        foreach ($numbers as $index => $number) {
            $pax = is_array($passengers[$index] ?? null) ? $passengers[$index] : [];
            $name = strtoupper(trim(implode(' ', array_filter([
                $pax['title'] ?? '',
                $pax['first_name'] ?? '',
                $pax['last_name'] ?? '',
            ]))));

            $tickets[] = [
                'ticket_number' => $number,
                'passenger_name' => $name,
                'ticket_status' => 'Issued',
                'pnr' => $booking->sabre_record_locator,
                'plating_carrier' => strtoupper(trim((string) (
                    $itinerary['validating_carrier']
                    ?? data_get($itinerary, 'legs.0.segments.0.carrier')
                    ?? ''
                ))),
                'issued_date' => null,
                'refundable' => array_key_exists('non_refundable', $itinerary)
                    ? (! empty($itinerary['non_refundable']) ? 'Non-refundable' : 'Refundable')
                    : null,
                'total_price' => null,
                'base_price' => null,
                'taxes' => null,
                'fare_basis' => trim((string) ($itinerary['fare_basis'] ?? '')),
                'coupons' => $coupons,
            ];
        }

        return $tickets;
    }

    /**
     * @param  array<string, mixed>  $itinerary
     * @return list<array<string, mixed>>
     */
    private static function couponsFromItinerary(array $itinerary): array
    {
        $coupons = [];

        foreach ($itinerary['legs'] ?? [] as $leg) {
            if (! is_array($leg)) {
                continue;
            }

            foreach ($leg['segments'] ?? [] as $segment) {
                if (! is_array($segment)) {
                    continue;
                }

                $carrier = strtoupper(trim((string) ($segment['carrier'] ?? '')));
                $flightNumber = trim((string) ($segment['flight_number'] ?? ''));
                $from = strtoupper(trim((string) ($segment['from'] ?? '')));
                $to = strtoupper(trim((string) ($segment['to'] ?? '')));

                $coupons[] = [
                    'coupon_number' => (string) (count($coupons) + 1),
                    'flight' => trim($carrier . ' ' . $flightNumber),
                    'route' => $from !== '' && $to !== '' ? "{$from} → {$to}" : '',
                    'departure' => trim((string) (
                        $segment['departure_display']
                        ?? $segment['departure_clock']
                        ?? ''
                    )),
                    'booking_class' => strtoupper(trim((string) ($segment['booking_code'] ?? ''))),
                    'fare_basis' => trim((string) ($segment['fare_basis'] ?? '')),
                    'status' => 'Open',
                ];
            }
        }

        return $coupons;
    }
}
