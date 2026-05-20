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
        $rooms = self::buildTboRoomRows($detail ?? $savedResponse);

        $sections = [];

        if ($rows['confirmation'] !== []) {
            $sections[] = [
                'title' => 'Confirmation',
                'icon' => 'bx-check-shield',
                'tone' => 'purple',
                'rows' => $rows['confirmation'],
            ];
        }

        if ($rows['stay'] !== []) {
            $sections[] = [
                'title' => 'Stay details',
                'icon' => 'bx-hotel',
                'tone' => 'blue',
                'rows' => $rows['stay'],
            ];
        }

        if ($rooms !== []) {
            $sections[] = [
                'title' => 'Rooms & guests',
                'icon' => 'bx-bed',
                'tone' => 'slate',
                'rows' => $rooms,
            ];
        }

        if ($rows['financial'] !== []) {
            $sections[] = [
                'title' => 'Supplier pricing',
                'icon' => 'bx-receipt',
                'tone' => 'green',
                'rows' => $rows['financial'],
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
     * @return array{confirmation: list<array<string, mixed>>, stay: list<array<string, mixed>>, financial: list<array<string, mixed>>, policy: list<array<string, mixed>>}
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

        $hotelName = self::readTboValue($source, ['HotelName']) ?: $booking->hotel_name;
        $address = trim(implode(', ', array_filter([
            self::readTboValue($source, ['AddressLine1']),
            self::readTboValue($source, ['AddressLine2']),
            self::readTboValue($source, ['City']),
        ]))) ?: $booking->hotel_address;

        $stay = self::filterRows([
            self::row('Hotel', $hotelName),
            self::row('Rating', self::formatTboRating(self::readTboValue($source, ['Rating']))),
            self::row('Address', $address),
            self::row('Check-in', self::formatTboDate(self::readTboValue($source, ['CheckInDate'])) ?: ($booking->check_in_date?->format('d M Y'))),
            self::row('Check-out', self::formatTboDate(self::readTboValue($source, ['CheckOutDate'])) ?: ($booking->check_out_date?->format('d M Y'))),
            self::row('Booked on (supplier)', self::formatTboDate(self::readTboValue($source, ['BookingDate'])) ?: $booking->created_at?->format('d M Y, h:i A')),
            self::row('Rooms booked', self::readTboValue($source, ['NoOfRooms'])),
        ]);

        $currency = self::readTboValue($source, ['Currency']) ?: $booking->currency;
        $totalFare = self::readTboValue($source, ['TotalFare', 'TotalAmount', 'Price']);

        $financial = self::filterRows([
            self::row('Total fare', self::formatMoney($totalFare, $currency)),
            self::row('Currency', $currency),
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
            self::row('Special request', self::readTboValue($source, ['SpecialRequest'])),
        ]);

        return compact('confirmation', 'stay', 'financial', 'policy');
    }

    /**
     * @param  array<string, mixed>|null  $source
     * @return list<array<string, mixed>>
     */
    private static function buildTboRoomRows(?array $source): array
    {
        if ($source === null) {
            return [];
        }

        $roomTypes = data_get($source, 'Roomtype.RoomDetails')
            ?? data_get($source, 'Roomtype')
            ?? data_get($source, 'Rooms');

        if (! is_array($roomTypes)) {
            return [];
        }

        if (! array_is_list($roomTypes)) {
            $roomTypes = [$roomTypes];
        }

        $rows = [];

        foreach ($roomTypes as $index => $room) {
            if (! is_array($room)) {
                continue;
            }

            $roomNo = $index + 1;
            $roomName = self::readTboValue($room, ['RoomName', 'RoomTypeName']);
            $adults = self::readTboValue($room, ['AdultCount', 'Adults']);
            $children = self::readTboValue($room, ['ChildCount', 'Children']);
            $meal = self::readTboValue($room, ['MealType', 'BoardType', 'Ameneties']);

            $occupancy = trim(collect([
                $adults !== null ? "{$adults} adult" . ((int) $adults === 1 ? '' : 's') : null,
                $children !== null ? "{$children} child" . ((int) $children === 1 ? '' : 'ren') : null,
            ])->filter()->implode(' · '));

            $guestNames = self::extractGuestNames($room);

            $value = collect([
                $roomName,
                $occupancy !== '' ? $occupancy : null,
                $meal ? 'Meal: ' . $meal : null,
                $guestNames !== [] ? 'Guests: ' . implode(', ', $guestNames) : null,
            ])->filter()->implode("\n");

            if ($value === '') {
                continue;
            }

            $rows[] = self::row('Room ' . $roomNo, $value, ['multiline' => true]);
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $room
     * @return list<string>
     */
    private static function extractGuestNames(array $room): array
    {
        $guestInfo = data_get($room, 'GuestInfo.Guest') ?? data_get($room, 'GuestInfo') ?? data_get($room, 'CustomerNames');

        if (! is_array($guestInfo)) {
            return [];
        }

        if (! array_is_list($guestInfo)) {
            $guestInfo = [$guestInfo];
        }

        $names = [];

        foreach ($guestInfo as $guest) {
            if (! is_array($guest)) {
                continue;
            }

            $title = self::readTboValue($guest, ['Title', 'GuestTitle']);
            $first = self::readTboValue($guest, ['FirstName', 'GivenName']);
            $last = self::readTboValue($guest, ['LastName', 'Surname']);
            $full = trim(implode(' ', array_filter([$title, $first, $last])));

            if ($full !== '') {
                $names[] = $full;
            }
        }

        return $names;
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

    private static function formatTboDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('d M Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private static function formatTboRating(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $rating = (string) $value;

        return str_replace('Star', ' star', preg_replace('/([A-Z])/', ' $1', $rating) ?? $rating);
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

    private static function formatMoney(mixed $amount, mixed $currency): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $formatted = number_format((float) $amount, 2, '.', ',');

        return trim($formatted . ($currency ? ' ' . $currency : ''));
    }
}
