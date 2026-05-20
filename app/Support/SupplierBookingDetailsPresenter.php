<?php

namespace App\Support;

use App\Models\B2bHotelBooking;

final class SupplierBookingDetailsPresenter
{
    /**
     * @param  array<string, mixed>|null  $liveFetch  Result from TboBookingDetailTestService::fetch()
     * @return array<string, mixed>|null
     */
    public static function present(B2bHotelBooking $booking, ?array $liveFetch = null): ?array
    {
        $supplier = strtolower((string) ($booking->supplier ?? ''));

        if ($supplier !== 'tbo') {
            return null;
        }

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
            return [
                'supplier_label' => formatBookingSupplierLabel($booking->supplier, 'TBO'),
                'source' => 'unavailable',
                'error' => $liveFetch['error'] ?? 'No supplier confirmation data is stored for this booking.',
                'status' => null,
                'sections' => [],
            ];
        }

        $refundMeta = HotelRefundPresentation::tboRefundMetaFromBookingResponse($detail ?? $savedResponse);

        $rows = self::buildTboRows($booking, $detail, $savedResponse, $refundMeta);

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

        return [
            'supplier_label' => formatBookingSupplierLabel($booking->supplier, 'TBO'),
            'source' => $source,
            'error' => ($liveFetch !== null && empty($liveFetch['ok'])) ? ($liveFetch['error'] ?? null) : null,
            'status' => self::resolveTboStatusBadge($detail, $savedResponse, $booking),
            'sections' => $sections,
        ];
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
        return self::readTboValue($data, ['ConfirmationNo', 'ConfirmationNumber', 'BookingReferenceId', 'HotelName']) !== null;
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
            self::row('Supplier status', self::readTboValue($source, ['BookingStatus', '@BookingStatus']), ['badge' => true]),
            self::row('Confirmation no.', self::readTboValue($source, ['ConfirmationNo', 'ConfirmationNumber', 'BookingReferenceId', 'BookingRef']) ?: $booking->yalago_booking_reference, ['mono' => true]),
            self::row('TBO booking ID', self::readTboValue($source, ['BookingId', '@BookingId']), ['mono' => true]),
            self::row('Hotel confirmation', self::readTboValue($source, ['HotelConfirmationNo', '@HotelConfirmationNo']), ['mono' => true]),
            self::row('Supplier reference', self::readTboValue($source, ['SupplierReferenceNo', '@SupplierReferenceNo']), ['mono' => true]),
            self::row('Invoice number', self::readTboValue($source, ['InvoiceNumber', '@InvoiceNumber']), ['mono' => true]),
            self::row('Voucher status', self::formatBooleanLabel(self::readTboValue($source, ['VoucherStatus', '@VoucherStatus']))),
            self::row('Client reference', data_get($booking->booking_request, 'ClientReferenceId') ?: $booking->booking_number, ['mono' => true]),
        ]);

        $cancelPolicy = self::readTboValue($source, ['HotelCancelPolicies', 'CancelPolicies']);
        $cancelText = is_array($cancelPolicy)
            ? (self::readTboValue($cancelPolicy, ['AutoCancellationText', 'CancelPolicy', 'DefaultPolicy', 'NoShowPolicy'])
                ?: self::readTboValue($cancelPolicy, ['LastCancellationDeadline']))
            : (is_string($cancelPolicy) ? $cancelPolicy : null);

        $policyDetails = self::readTboValue($source, ['HotelPolicyDetails']);

        $policy = self::filterRows([
            self::row('Refundability', self::formatRefundability($refundMeta)),
            self::row('Cancellation policy', $cancelText),
            self::row('Hotel policy', is_string($policyDetails) ? $policyDetails : null),
        ]);

        return compact('confirmation', 'policy');
    }

    /**
     * @param  array<string, mixed>|null  $detail
     * @param  array<string, mixed>|null  $savedResponse
     * @return array{label: string, tone: string}|null
     */
    private static function resolveTboStatusBadge(?array $detail, ?array $savedResponse, B2bHotelBooking $booking): ?array
    {
        $status = self::readTboValue($detail ?? $savedResponse ?? [], ['BookingStatus', '@BookingStatus'])
            ?: $booking->booking_status;

        if ($status === null || trim((string) $status) === '') {
            return null;
        }

        $label = ucfirst(strtolower((string) $status));
        $normalized = strtolower((string) $status);

        $tone = match (true) {
            in_array($normalized, ['confirmed', 'vouchered', 'completed'], true) => 'success',
            in_array($normalized, ['pending', 'cancellationinprogress'], true) => 'warning',
            in_array($normalized, ['cancelled', 'canceled', 'failed', 'rejected'], true) => 'danger',
            default => 'secondary',
        };

        return compact('label', 'tone');
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     */
    private static function readTboValue(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
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
     * @param  list<array<string, mixed>|null>  $rows
     * @return list<array<string, mixed>>
     */
    private static function filterRows(array $rows): array
    {
        return array_values(array_filter($rows, fn ($row) => $row !== null && ($row['value'] ?? '') !== '' && ($row['value'] ?? null) !== null));
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
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
}
