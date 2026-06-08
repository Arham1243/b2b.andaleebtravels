<?php

namespace App\Support;

use App\Models\B2bFlightBooking;

final class SupplierFlightBookingDetailsPresenter
{
    /**
     * @param  array<string, mixed>|null  $liveFetch
     * @return array<string, mixed>|null
     */
    public static function present(B2bFlightBooking $booking, ?array $liveFetch = null): ?array
    {
        if (empty($booking->sabre_record_locator)) {
            return null;
        }

        if ($booking->isTravelport()) {
            return self::presentTravelport($booking);
        }

        $liveResponse = is_array($liveFetch['response'] ?? null) ? $liveFetch['response'] : null;
        $savedResponse = self::normalizeFromSavedResponses($booking);

        $normalized = $liveResponse ?? $savedResponse;

        $source = 'saved';
        if ($liveFetch !== null && ! empty($liveFetch['ok'])) {
            $source = 'live';
        } elseif ($liveFetch !== null && ! empty($liveFetch['error'])) {
            $source = $normalized !== null ? 'saved' : 'unavailable';
        }

        if ($normalized === null) {
            return [
                'supplier_label' => 'Sabre',
                'source' => 'unavailable',
                'error' => $liveFetch['error'] ?? 'No Sabre confirmation data is available for this booking.',
                'status' => self::resolveStatusBadge($booking->booking_status, $booking),
                'sections' => [],
            ];
        }

        if ($savedResponse !== null) {
            $normalized = self::mergeSavedTicketing($normalized, $savedResponse);
        }

        $rows = self::buildRows($booking, $normalized);

        return [
            'supplier_label' => 'Sabre',
            'source' => $source,
            'error' => ($liveFetch !== null && empty($liveFetch['ok'])) ? ($liveFetch['error'] ?? null) : null,
            'status' => self::resolveStatusBadge($normalized['bookingStatus'] ?? $booking->booking_status, $booking),
            'sections' => self::buildSections($rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function presentTravelport(B2bFlightBooking $booking): array
    {
        $itinerary = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
        $pricingData = is_array(data_get($booking->booking_request, 'pricing_data'))
            ? data_get($booking->booking_request, 'pricing_data')
            : [];

        $platingCarrier = trim((string) (
            $itinerary['validating_carrier']
            ?? data_get($itinerary, 'legs.0.segments.0.carrier')
            ?? ($pricingData['carrier'] ?? '')
        ));

        $ticketNumbers = FlightBookingTicketResolver::forBooking($booking);
        $ticketValue = $ticketNumbers !== [] ? implode(', ', $ticketNumbers) : null;

        $confirmation = self::filterRows([
            self::row('Supplier status', $booking->displayBookingStatus(), ['badge' => true]),
            self::row('Air reservation locator', $booking->sabre_record_locator, ['mono' => true]),
            self::row('Universal record locator', data_get($booking->booking_response, 'travelport_universal_locator'), ['mono' => true]),
            self::row('Plating carrier', $platingCarrier !== '' ? strtoupper($platingCarrier) : null),
            self::row('Ticket status', $booking->ticket_status, ['badge' => true]),
            self::row('Ticket number(s)', $ticketValue, ['mono' => true]),
            self::row('Latest ticketing time', data_get($pricingData, 'latest_ticketing_time')),
        ]);

        $refundability = self::resolveRefundability($booking);

        $policy = self::filterRows([
            self::row('Refundability', $refundability),
            self::row('Cancellation policy', self::cancellationPolicySummary($booking, $refundability), ['multiline' => true]),
            self::row('Hold expires', self::formatHoldExpiry($booking)),
        ]);

        return [
            'supplier_label' => 'Travelport',
            'source' => 'saved',
            'error' => null,
            'status' => self::resolveStatusBadge($booking->booking_status, $booking),
            'sections' => self::buildSections(compact('confirmation', 'policy')),
        ];
    }

    /**
     * @param  array<string, mixed>  $live
     * @param  array<string, mixed>  $saved
     * @return array<string, mixed>
     */
    private static function mergeSavedTicketing(array $live, array $saved): array
    {
        if (empty($live['tickets']) && ! empty($saved['tickets'])) {
            $live['tickets'] = $saved['tickets'];
        }

        if (empty($live['ticketStatus']) && ! empty($saved['ticketStatus'])) {
            $live['ticketStatus'] = $saved['ticketStatus'];
        }

        return $live;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function normalizeFromSavedResponses(B2bFlightBooking $booking): ?array
    {
        $bookingResponse = is_array($booking->booking_response) ? $booking->booking_response : null;
        $ticketResponse = is_array($booking->ticket_response) ? $booking->ticket_response : null;

        if ($bookingResponse === null && $ticketResponse === null) {
            return null;
        }

        return [
            'confirmationId' => $booking->sabre_record_locator
                ?: data_get($bookingResponse, 'CreatePassengerNameRecordRS.ItineraryRef.ID'),
            'bookingStatus' => $booking->booking_status,
            'ticketStatus' => $booking->ticket_status,
            'tickets' => FlightBookingTicketResolver::fromResponse($ticketResponse),
            'travelers' => [],
            'flights' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return array{confirmation: list<array<string, mixed>>, policy: list<array<string, mixed>>}
     */
    private static function buildRows(B2bFlightBooking $booking, array $normalized): array
    {
        $ticketLines = $normalized['tickets'] ?? [];
        $ticketValue = $ticketLines !== []
            ? implode(', ', $ticketLines)
            : null;

        $confirmation = self::filterRows([
            self::row('Supplier status', $normalized['bookingStatus'] ?? $booking->booking_status, ['badge' => true]),
            self::row('PNR / Record locator', $normalized['confirmationId'] ?? $booking->sabre_record_locator, ['mono' => true]),
            self::row('Ticket status', $normalized['ticketStatus'] ?? $booking->ticket_status, ['badge' => true]),
            self::row('Ticket number(s)', $ticketValue, ['mono' => true]),
        ]);

        $refundability = self::resolveRefundability($booking);

        $policy = self::filterRows([
            self::row('Refundability', $refundability),
            self::row('Cancellation policy', self::cancellationPolicySummary($booking, $refundability), ['multiline' => true]),
            self::row('Hold expires', self::formatHoldExpiry($booking)),
        ]);

        return compact('confirmation', 'policy');
    }

    /**
     * @param  array{confirmation: list<array<string, mixed>>, policy: list<array<string, mixed>>}  $rows
     * @return list<array<string, mixed>>
     */
    private static function buildSections(array $rows): array
    {
        $sections = [];

        if ($rows['confirmation'] !== []) {
            $sections[] = [
                'title' => 'Confirmation',
                'icon' => 'bx-check-shield',
                'tone' => 'purple',
                'rows' => $rows['confirmation'],
            ];
        }

        if ($rows['policy'] !== []) {
            $sections[] = [
                'title' => 'Cancellation & policy',
                'icon' => 'bx-info-circle',
                'tone' => 'slate',
                'rows' => $rows['policy'],
            ];
        }

        return $sections;
    }

    private static function resolveRefundability(B2bFlightBooking $booking): ?string
    {
        $itinerary = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];

        if (array_key_exists('non_refundable', $itinerary)) {
            return ! empty($itinerary['non_refundable']) ? 'Non-refundable' : 'Refundable';
        }

        $passengerFare = data_get($booking->search_response, 'groupedItineraryResponse.itineraryGroups.0.itineraries.0.pricingInformation.0.fare.passengerInfoList.0.passengerInfo');
        if (is_array($passengerFare) && array_key_exists('nonRefundable', $passengerFare)) {
            return ! empty($passengerFare['nonRefundable']) ? 'Non-refundable' : 'Refundable';
        }

        return null;
    }

    private static function cancellationPolicySummary(B2bFlightBooking $booking, ?string $refundability): ?string
    {
        if ($booking->isOnHold()) {
            return 'Unticketed hold — airline fare rules apply when ticketing.';
        }

        return match ($refundability) {
            'Non-refundable' => 'Non-refundable fare. Cancellation may not return ticket value; airline penalties apply.',
            'Refundable' => 'Refundable fare — airline cancellation penalties and rules apply.',
            default => 'Airline fare rules govern cancellation and refund eligibility.',
        };
    }

    private static function formatHoldExpiry(B2bFlightBooking $booking): ?string
    {
        if (! $booking->isOnHold()) {
            return null;
        }

        $expiry = $booking->hold_expires_at ?? $booking->created_at?->copy()->addHour();
        if ($expiry === null) {
            return null;
        }

        $label = $expiry->format('d M Y, h:i A');

        return $expiry->isPast() ? "{$label} (expired)" : $label;
    }

    /**
     * @return array{label: string, class: string}|null
     */
    private static function resolveStatusBadge(mixed $supplierStatus, B2bFlightBooking $booking): ?array
    {
        if ($booking->isPaid() && $booking->ticket_status === 'issued') {
            return ['label' => 'Confirmed', 'class' => 'confirmed'];
        }

        $status = $supplierStatus ?: $booking->booking_status;

        if ($status === null || trim((string) $status) === '') {
            return null;
        }

        $label = ucfirst(strtolower(str_replace('_', ' ', (string) $status)));
        $normalized = strtolower((string) $status);

        $class = match (true) {
            in_array($normalized, ['confirmed', 'completed', 'ticketed', 'issued'], true) => 'confirmed',
            in_array($normalized, ['hold', 'pending', 'on hold', 'on_hold'], true) => 'pending',
            in_array($normalized, ['cancelled', 'canceled', 'failed', 'rejected'], true) => 'cancelled',
            default => 'pending',
        };

        return compact('label', 'class');
    }

    /**
     * @param  list<array<string, mixed>|null>  $rows
     * @return list<array<string, mixed>>
     */
    private static function filterRows(array $rows): array
    {
        return array_values(array_filter($rows, fn ($row) => $row !== null && ($row['value'] ?? '') !== '' && ($row['value'] ?? null) !== null));
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>|null
     */
    private static function row(string $label, mixed $value, array $options = []): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        return array_merge([
            'label' => $label,
            'value' => is_bool($value) ? ($value ? 'Yes' : 'No') : (string) $value,
        ], $options);
    }
}
