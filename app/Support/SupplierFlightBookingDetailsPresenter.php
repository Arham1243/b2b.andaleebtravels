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
            'tickets' => self::extractTicketNumbers($ticketResponse),
            'travelers' => [],
            'flights' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return array{confirmation: list<array<string, mixed>>, ticketing: list<array<string, mixed>>}
     */
    private static function buildRows(B2bFlightBooking $booking, array $normalized): array
    {
        $confirmation = self::filterRows([
            self::row('Supplier status', $normalized['bookingStatus'] ?? null, ['badge' => true]),
            self::row('PNR / Record locator', $normalized['confirmationId'] ?? $booking->sabre_record_locator, ['mono' => true]),
            self::row('Sabre booking ID', $normalized['bookingId'] ?? null, ['mono' => true]),
            self::row('Our booking #', $booking->booking_number, ['mono' => true]),
            self::row('Data source', $normalized['apiSource'] ?? null),
        ]);

        $ticketLines = $normalized['tickets'] ?? [];
        $ticketValue = $ticketLines !== []
            ? implode(', ', $ticketLines)
            : null;

        $ticketing = self::filterRows([
            self::row('Ticket status', $normalized['ticketStatus'] ?? $booking->ticket_status, ['badge' => true]),
            self::row('Ticket number(s)', $ticketValue, ['mono' => true]),
            self::row('Travelers (live)', self::formatTravelers($normalized['travelers'] ?? []), ['multiline' => true]),
            self::row('Segments (live)', self::formatFlights($normalized['flights'] ?? []), ['multiline' => true]),
        ]);

        return compact('confirmation', 'ticketing');
    }

    /**
     * @param  array{confirmation: list<array<string, mixed>>, ticketing: list<array<string, mixed>>}  $rows
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

        if ($rows['ticketing'] !== []) {
            $sections[] = [
                'title' => 'Ticketing',
                'icon' => 'bx-receipt',
                'tone' => 'slate',
                'rows' => $rows['ticketing'],
            ];
        }

        return $sections;
    }

    /**
     * @param  list<string>  $travelers
     */
    private static function formatTravelers(array $travelers): ?string
    {
        $lines = array_values(array_filter(array_map(
            fn ($name) => is_string($name) && trim($name) !== '' ? trim($name) : null,
            $travelers
        )));

        return $lines === [] ? null : implode("\n", $lines);
    }

    /**
     * @param  list<string>  $flights
     */
    private static function formatFlights(array $flights): ?string
    {
        $lines = array_values(array_filter(array_map(
            fn ($line) => is_string($line) && trim($line) !== '' ? trim($line) : null,
            $flights
        )));

        return $lines === [] ? null : implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>|null  $ticketResponse
     * @return list<string>
     */
    private static function extractTicketNumbers(?array $ticketResponse): array
    {
        if ($ticketResponse === null) {
            return [];
        }

        $numbers = [];

        $single = data_get($ticketResponse, 'AirTicketRS.ETicketNumber')
            ?? data_get($ticketResponse, 'eTicketNumber');
        if (is_string($single) && $single !== '') {
            $numbers[] = $single;
        }

        foreach (data_get($ticketResponse, 'AirTicketRS.TicketNumberInfo', []) as $info) {
            $num = data_get($info, 'TicketNumber');
            if (is_string($num) && $num !== '') {
                $numbers[] = $num;
            }
        }

        return array_values(array_unique($numbers));
    }

    /**
     * @return array{label: string, class: string}|null
     */
    private static function resolveStatusBadge(mixed $supplierStatus, B2bFlightBooking $booking): ?array
    {
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
