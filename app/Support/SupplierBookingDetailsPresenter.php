<?php

namespace App\Support;

use App\Models\B2bHotelBooking;
use Carbon\Carbon;

final class SupplierBookingDetailsPresenter
{
    /**
     * @param  array<string, mixed>|null  $liveFetch  Result from TboBookingDetailTestService::fetch()
     * @return array<string, mixed>|null
     */
    public static function present(B2bHotelBooking $booking, ?array $liveFetch = null): ?array
    {
        return match (strtolower((string) ($booking->supplier ?? ''))) {
            'tbo' => self::presentTbo($booking, $liveFetch),
            'yalago' => self::presentYalago($booking),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>|null  $liveFetch
     * @return array<string, mixed>
     */
    private static function presentTbo(B2bHotelBooking $booking, ?array $liveFetch): array
    {
        $liveResponse = is_array($liveFetch['response'] ?? null) ? $liveFetch['response'] : null;
        $savedResponse = is_array($booking->booking_response) ? $booking->booking_response : null;
        $detail = self::extractTboDetailNode($liveResponse) ?? self::extractTboDetailNode($savedResponse);

        $source = 'saved';
        if ($liveFetch !== null && ! empty($liveFetch['ok'])) {
            $source = 'live';
        } elseif ($liveFetch !== null && ! empty($liveFetch['error'])) {
            $source = $detail !== null ? 'saved' : 'unavailable';
        }

        if ($detail === null && $savedResponse === null) {
            return self::emptyResult($booking, $liveFetch['error'] ?? 'No supplier confirmation data is stored for this booking.');
        }

        $refundMeta = HotelRefundPresentation::tboRefundMetaFromBookingResponse($detail ?? $savedResponse);
        $rows = self::buildTboRows($booking, $detail, $savedResponse, $refundMeta);

        return [
            'supplier_label' => formatBookingSupplierLabel($booking->supplier, 'TBO'),
            'source' => $source,
            'error' => ($liveFetch !== null && empty($liveFetch['ok'])) ? ($liveFetch['error'] ?? null) : null,
            'status' => self::resolveStatusBadge(
                self::readValue($detail ?? $savedResponse ?? [], ['BookingStatus', '@BookingStatus']),
                $booking
            ),
            'sections' => self::buildSections($rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function presentYalago(B2bHotelBooking $booking): array
    {
        $savedResponse = is_array($booking->booking_response) ? $booking->booking_response : null;

        if ($savedResponse === null && ! $booking->yalago_booking_reference) {
            return self::emptyResult($booking, 'No Yalago confirmation response is saved for this booking.');
        }

        $rows = self::buildYalagoRows($booking, $savedResponse ?? []);

        return [
            'supplier_label' => formatBookingSupplierLabel($booking->supplier, 'Yalago'),
            'source' => 'saved',
            'error' => null,
            'status' => self::resolveStatusBadge(
                self::readValue($savedResponse ?? [], ['BookingStatus', 'Status', 'State']),
                $booking
            ),
            'sections' => self::buildSections($rows),
        ];
    }

    /**
     * @return array{confirmation: list<array<string, mixed>>, policy: list<array<string, mixed>>}
     */
    private static function buildYalagoRows(B2bHotelBooking $booking, array $source): array
    {
        $confirmation = self::filterRows([
            self::row('Supplier status', self::readValue($source, ['BookingStatus', 'Status', 'State']), ['badge' => true]),
            self::row('Confirmation no.', self::readValue($source, ['BookingRef']) ?: $booking->yalago_booking_reference, ['mono' => true]),
            self::row('Affiliate reference', self::readValue($source, ['AffiliateRef']) ?: data_get($booking->booking_request, 'AffiliateRef') ?: $booking->booking_number, ['mono' => true]),
            self::row('Establishment ID', self::readValue($source, ['EstablishmentId']) ?: $booking->yalago_hotel_id, ['mono' => true]),
            self::row('Supplier total', self::formatMoney(
                self::readValue($source, ['TotalPrice.Amount', 'TotalAmount', 'Total']),
                self::readValue($source, ['TotalPrice.Currency', 'Currency']) ?: $booking->currency
            )),
        ]);

        $policy = self::filterRows([
            self::row('Refundability', self::yalagoRefundabilityLabel($source, $booking)),
            self::row('Cancellation policy', self::yalagoCancellationSummary($source, $booking)),
            self::row('Cancellable', self::formatYesNo(self::readValue($source, ['IsCancellable']))),
        ]);

        return compact('confirmation', 'policy');
    }

    /**
     * @param  array<string, mixed>|null  $response
     * @return array<string, mixed>|null
     */
    private static function extractTboDetailNode(?array $response): ?array
    {
        if ($response === null) {
            return null;
        }

        foreach (['BookingDetail', 'HotelBookingDetail', 'BookResult'] as $key) {
            $node = data_get($response, $key);
            if (is_array($node)) {
                return $node;
            }
        }

        if (self::looksLikeTboDetail($response)) {
            return $response;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function looksLikeTboDetail(array $data): bool
    {
        return self::readValue($data, ['ConfirmationNo', 'ConfirmationNumber', 'BookingReferenceId', 'HotelName']) !== null;
    }

    /**
     * @param  array<string, mixed>|null  $detail
     * @param  array<string, mixed>|null  $savedResponse
     * @return array{confirmation: list<array<string, mixed>>, policy: list<array<string, mixed>>}
     */
    private static function buildTboRows(
        B2bHotelBooking $booking,
        ?array $detail,
        ?array $savedResponse,
        array $refundMeta
    ): array {
        $source = $detail ?? $savedResponse ?? [];

        $confirmation = self::filterRows([
            self::row('Supplier status', self::readValue($source, ['BookingStatus', '@BookingStatus']), ['badge' => true]),
            self::row('Confirmation no.', self::readValue($source, ['ConfirmationNo', 'ConfirmationNumber', 'BookingReferenceId', 'BookingRef']) ?: $booking->yalago_booking_reference, ['mono' => true]),
            self::row('TBO booking ID', self::readValue($source, ['BookingId', '@BookingId']), ['mono' => true]),
            self::row('Hotel confirmation', self::readValue($source, ['HotelConfirmationNo', '@HotelConfirmationNo']), ['mono' => true]),
            self::row('Supplier reference', self::readValue($source, ['SupplierReferenceNo', '@SupplierReferenceNo']), ['mono' => true]),
            self::row('Invoice number', self::readValue($source, ['InvoiceNumber', '@InvoiceNumber']), ['mono' => true]),
            self::row('Voucher status', self::formatBooleanLabel(self::readValue($source, ['VoucherStatus', '@VoucherStatus']))),
            self::row('Client reference', data_get($booking->booking_request, 'ClientReferenceId') ?: $booking->booking_number, ['mono' => true]),
        ]);

        $cancelPolicy = self::readValue($source, ['HotelCancelPolicies', 'CancelPolicies']);
        $cancelText = is_array($cancelPolicy)
            ? (self::readValue($cancelPolicy, ['AutoCancellationText', 'CancelPolicy', 'DefaultPolicy', 'NoShowPolicy'])
                ?: self::readValue($cancelPolicy, ['LastCancellationDeadline']))
            : (is_string($cancelPolicy) ? $cancelPolicy : null);

        $policyDetails = self::readValue($source, ['HotelPolicyDetails']);

        $policy = self::filterRows([
            self::row('Refundability', self::formatRefundability($refundMeta)),
            self::row('Cancellation policy', $cancelText),
            self::row('Hotel policy', is_string($policyDetails) ? $policyDetails : null),
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

    /**
     * @return array<string, mixed>
     */
    private static function emptyResult(B2bHotelBooking $booking, string $error): array
    {
        return [
            'supplier_label' => formatBookingSupplierLabel($booking->supplier, 'Supplier'),
            'source' => 'unavailable',
            'error' => $error,
            'status' => null,
            'sections' => [],
        ];
    }

    /**
     * @return array{label: string, class: string}|null
     */
    private static function resolveStatusBadge(mixed $supplierStatus, B2bHotelBooking $booking): ?array
    {
        $status = $supplierStatus ?: $booking->booking_status;

        if ($status === null || trim((string) $status) === '') {
            return null;
        }

        $label = ucfirst(strtolower((string) $status));
        $normalized = strtolower((string) $status);

        $class = match (true) {
            in_array($normalized, ['confirmed', 'vouchered', 'completed'], true) => 'confirmed',
            in_array($normalized, ['pending', 'cancellationinprogress'], true) => 'pending',
            in_array($normalized, ['cancelled', 'canceled', 'failed', 'rejected'], true) => 'cancelled',
            default => 'pending',
        };

        return compact('label', 'class');
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     */
    private static function readValue(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (str_contains($key, '.')) {
                $value = data_get($data, $key);
                if ($value !== null && $value !== '') {
                    return $value;
                }

                continue;
            }

            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            if ($value === null || $value === '') {
                continue;
            }

            return $value;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private static function yalagoRefundabilityLabel(array $source, B2bHotelBooking $booking): ?string
    {
        foreach (data_get($source, 'Rooms', []) as $room) {
            if (! is_array($room)) {
                continue;
            }

            if (! empty($room['NonRefundable'])) {
                return 'Non-refundable';
            }

            $board = $room['Board'] ?? null;
            if (is_array($board) && ! empty($board['NonRefundable'])) {
                return 'Non-refundable';
            }

            if (is_array($board) && array_key_exists('NonRefundable', $board) && empty($board['NonRefundable'])) {
                return 'Refundable';
            }
        }

        if (! empty($source['NonRefundable'])) {
            return 'Non-refundable';
        }

        $selectedRooms = is_array($booking->selected_rooms) ? $booking->selected_rooms : [];
        foreach ($selectedRooms as $room) {
            if (! is_array($room)) {
                continue;
            }

            if (array_key_exists('non_refundable', $room)) {
                return ! empty($room['non_refundable']) ? 'Non-refundable' : 'Refundable';
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private static function yalagoCancellationSummary(array $source, B2bHotelBooking $booking): ?string
    {
        $staticCharges = data_get($source, 'CancellationPolicyStatic.0.CancellationCharges');
        if (is_array($staticCharges) && $staticCharges !== []) {
            $formatted = self::formatYalagoCancellationCharges($staticCharges);
            if ($formatted !== null) {
                return $formatted;
            }
        }

        foreach (data_get($source, 'Rooms', []) as $room) {
            if (! is_array($room)) {
                continue;
            }

            foreach ([$room['Board'] ?? null, $room] as $board) {
                if (! is_array($board)) {
                    continue;
                }

                $summary = HotelRefundPresentation::yalagoBoardSummary($board);
                if ($summary !== null) {
                    return $summary;
                }
            }
        }

        $summary = HotelRefundPresentation::yalagoBoardSummary($source);
        if ($summary !== null) {
            return $summary;
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $charges
     */
    private static function formatYalagoCancellationCharges(array $charges): ?string
    {
        $lines = [];

        foreach ($charges as $charge) {
            if (! is_array($charge)) {
                continue;
            }

            $amount = data_get($charge, 'Charge.Amount');
            $currency = data_get($charge, 'Charge.Currency', '');
            $expiry = data_get($charge, 'ExpiryDate') ?? data_get($charge, 'ExpiryDateUTC');

            if ($amount === null || $expiry === null) {
                continue;
            }

            try {
                $date = Carbon::parse($expiry)->format('d M Y');
            } catch (\Throwable) {
                $date = (string) $expiry;
            }

            $lines[] = ((float) $amount <= 0)
                ? "Free cancellation until {$date}"
                : trim("{$currency} " . number_format((float) $amount, 2) . " charge from {$date}");
        }

        return $lines === [] ? null : implode(' · ', $lines);
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

    private static function formatBooleanLabel(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Vouchered' : 'Not vouchered';
        }

        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['true', '1', 'yes'], true)) {
            return 'Vouchered';
        }

        if (in_array($normalized, ['false', '0', 'no'], true)) {
            return 'Not vouchered';
        }

        return (string) $value;
    }

    private static function formatYesNo(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['true', '1', 'yes'], true)) {
            return 'Yes';
        }

        if (in_array($normalized, ['false', '0', 'no'], true)) {
            return 'No';
        }

        return (string) $value;
    }

    /**
     * @param  array{is_refundable: bool|null, summary: string|null}  $refundMeta
     */
    private static function formatRefundability(array $refundMeta): ?string
    {
        if ($refundMeta['is_refundable'] === true) {
            return 'Refundable';
        }

        if ($refundMeta['is_refundable'] === false) {
            return 'Non-refundable';
        }

        return $refundMeta['summary'] ?? null;
    }

    private static function formatMoney(mixed $amount, mixed $currency): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        if (is_array($amount)) {
            $currency = $amount['Currency'] ?? $currency;
            $amount = $amount['Amount'] ?? null;
        }

        if ($amount === null || $amount === '') {
            return null;
        }

        $formatted = number_format((float) $amount, 2, '.', ',');

        return trim($formatted . ($currency ? ' ' . $currency : ''));
    }
}
