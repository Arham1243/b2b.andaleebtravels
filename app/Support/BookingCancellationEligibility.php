<?php

namespace App\Support;

use App\Models\B2bFlightBooking;
use App\Models\B2bHotelBooking;
use App\Services\HotelService;
use Carbon\Carbon;

final class BookingCancellationEligibility
{
    /**
     * @return array{
     *     can_cancel: bool,
     *     reason: string|null,
     *     is_refundable: bool|null,
     *     flow: 'yalago_modal'|'tbo_direct'|null,
     *     policy_summary: string|null,
     *     deadline: Carbon|null,
     *     deadline_formatted: string|null,
     *     needs_live_policy: bool,
     *     policy_fetch_failed: bool,
     * }
     */
    /**
     * Load supplier cancellation rules for a booking detail page.
     *
     * @param  array<string, mixed>|null  $tboDetail  Admin TBO BookingDetail fetch payload
     * @return array<string, mixed>
     */
    public static function resolveForHotelPage(
        B2bHotelBooking $booking,
        ?HotelService $hotelService = null,
        ?array $tboDetail = null
    ): array {
        $eligibility = self::forHotel($booking, tboDetail: $tboDetail);

        if (!($eligibility['needs_live_policy'] ?? false) || $hotelService === null) {
            return $eligibility;
        }

        try {
            return self::forHotel(
                $booking,
                yalagoCharges: $hotelService->getCancellationCharges($booking),
                tboDetail: $tboDetail
            );
        } catch (\Throwable) {
            $eligibility['policy_fetch_failed'] = true;
            $eligibility['reason'] = 'Unable to load cancellation policy from Yalago. Please try again later or contact support.';

            return $eligibility;
        }
    }

    public static function forHotel(
        B2bHotelBooking $booking,
        ?array $yalagoCharges = null,
        ?array $tboDetail = null
    ): array {
        $base = [
            'can_cancel' => false,
            'reason' => null,
            'is_refundable' => null,
            'flow' => null,
            'policy_summary' => null,
            'deadline' => null,
            'deadline_formatted' => null,
            'needs_live_policy' => false,
            'policy_fetch_failed' => false,
        ];

        if ($booking->booking_status === 'cancelled') {
            return array_merge($base, ['reason' => 'Booking has already been cancelled.']);
        }

        if ($booking->booking_status !== 'confirmed' || $booking->payment_status !== 'paid') {
            return array_merge($base, ['reason' => 'Cancellation is only available for confirmed, paid bookings.']);
        }

        $supplier = strtolower((string) ($booking->supplier ?? 'yalago'));

        if ($supplier === 'tbo') {
            return self::forTboHotel($booking, $tboDetail);
        }

        return self::forYalagoHotel($booking, $yalagoCharges);
    }

    /**
     * @return array{
     *     can_cancel: bool,
     *     reason: string|null,
     *     is_refundable: bool|null,
     *     flow: null,
     *     policy_summary: string|null,
     *     deadline: Carbon|null,
     *     deadline_formatted: string|null,
     *     needs_live_policy: bool,
     *     policy_fetch_failed: bool,
     * }
     */
    public static function forFlight(B2bFlightBooking $booking): array
    {
        $base = [
            'can_cancel' => false,
            'reason' => null,
            'is_refundable' => null,
            'flow' => null,
            'policy_summary' => null,
            'deadline' => null,
            'deadline_formatted' => null,
            'needs_live_policy' => false,
            'policy_fetch_failed' => false,
        ];

        if ($booking->booking_status === 'cancelled') {
            return array_merge($base, ['reason' => 'Booking has already been cancelled.']);
        }

        if ($booking->isOnHold()) {
            return array_merge($base, ['reason' => 'Use release hold for bookings on hold.']);
        }

        if (! $booking->isConfirmed() || ! $booking->isPaid()) {
            return array_merge($base, ['reason' => 'Cancellation is only available for confirmed, paid bookings.']);
        }

        $nonRefundable = self::flightIsNonRefundable($booking);

        if ($nonRefundable !== false) {
            return array_merge($base, [
                'is_refundable' => false,
                'reason' => $nonRefundable === true
                    ? 'This is a non-refundable fare. Cancellation is not available for this booking.'
                    : 'Refund eligibility could not be verified. Cancellation is only available for refundable fares.',
                'policy_summary' => $nonRefundable === true
                    ? 'Non-refundable fare — ticket value cannot be recovered through cancellation.'
                    : null,
            ]);
        }

        return array_merge($base, [
            'can_cancel' => true,
            'is_refundable' => true,
            'policy_summary' => 'Refundable fare — airline cancellation penalties and rules apply.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $charges  Yalago getcancellationcharges response
     */
    public static function assertYalagoCanCancel(B2bHotelBooking $booking, array $charges): void
    {
        $eligibility = self::forYalagoHotel($booking, $charges);

        if (!$eligibility['can_cancel']) {
            throw new \RuntimeException($eligibility['reason'] ?? 'This booking cannot be cancelled.');
        }
    }

    public static function assertTboCanCancel(B2bHotelBooking $booking, ?array $tboDetail = null): void
    {
        $eligibility = self::forTboHotel($booking, $tboDetail);

        if (!$eligibility['can_cancel']) {
            throw new \RuntimeException($eligibility['reason'] ?? 'This booking cannot be cancelled.');
        }
    }

    public static function assertFlightCanCancel(B2bFlightBooking $booking): void
    {
        $eligibility = self::forFlight($booking);

        if (!$eligibility['can_cancel']) {
            throw new \RuntimeException($eligibility['reason'] ?? 'This booking cannot be cancelled.');
        }
    }

    public static function hotelIsRefundableForWalletRefund(
        B2bHotelBooking $booking,
        ?array $yalagoCharges = null,
        ?array $tboDetail = null
    ): bool {
        return self::forHotel($booking, $yalagoCharges, $tboDetail)['is_refundable'] === true;
    }

    public static function flightIsRefundableForWalletRefund(B2bFlightBooking $booking): bool
    {
        return self::forFlight($booking)['is_refundable'] === true;
    }

    /**
     * @return array<string, mixed>
     */
    private static function forYalagoHotel(B2bHotelBooking $booking, ?array $yalagoCharges): array
    {
        $base = [
            'can_cancel' => false,
            'reason' => null,
            'is_refundable' => null,
            'flow' => null,
            'policy_summary' => null,
            'deadline' => null,
            'deadline_formatted' => null,
            'needs_live_policy' => false,
            'policy_fetch_failed' => false,
        ];

        $storedRefundable = self::yalagoStoredRefundable($booking);

        if ($storedRefundable === false) {
            return array_merge($base, [
                'is_refundable' => false,
                'reason' => 'Booking is no longer refundable.',
            ]);
        }

        if ($yalagoCharges === null) {
            return array_merge($base, [
                'is_refundable' => $storedRefundable ?? true,
                'needs_live_policy' => true,
            ]);
        }

        if (!($yalagoCharges['IsCancellable'] ?? false)) {
            return array_merge($base, [
                'is_refundable' => $storedRefundable ?? true,
                'reason' => 'Cancellation deadline has expired.',
                'policy_summary' => self::yalagoPolicySummaryFromCharges($yalagoCharges),
            ]);
        }

        $window = self::yalagoCurrentChargeWindow($yalagoCharges);

        if ($window === null) {
            $deadline = self::yalagoLastCancellationDeadline($yalagoCharges);

            return array_merge($base, [
                'is_refundable' => $storedRefundable ?? true,
                'deadline' => $deadline,
                'deadline_formatted' => self::formatDeadline($deadline),
                'reason' => $deadline
                    ? 'Cancellation deadline has expired.'
                    : 'Cancellation deadline has expired.',
                'policy_summary' => self::yalagoPolicySummaryFromCharges($yalagoCharges),
            ]);
        }

        return array_merge($base, [
            'can_cancel' => true,
            'is_refundable' => $storedRefundable ?? true,
            'flow' => 'yalago_modal',
            'policy_summary' => self::yalagoPolicySummaryFromCharges($yalagoCharges),
            'deadline' => $window['deadline'],
            'deadline_formatted' => self::formatDeadline($window['deadline']),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function forTboHotel(B2bHotelBooking $booking, ?array $tboDetail): array
    {
        $base = [
            'can_cancel' => false,
            'reason' => null,
            'is_refundable' => null,
            'flow' => null,
            'policy_summary' => null,
            'deadline' => null,
            'deadline_formatted' => null,
            'needs_live_policy' => false,
            'policy_fetch_failed' => false,
        ];

        $source = self::tboPolicySource($booking, $tboDetail);
        $refundMeta = HotelRefundPresentation::tboRefundMetaFromBookingResponse($source);
        $isRefundable = $refundMeta['is_refundable'];

        if ($isRefundable === false) {
            return array_merge($base, [
                'is_refundable' => false,
                'reason' => 'Booking is no longer refundable.',
                'policy_summary' => $refundMeta['summary'],
            ]);
        }

        $deadline = self::tboCancellationDeadline($source);

        if ($deadline !== null && now()->greaterThan($deadline)) {
            return array_merge($base, [
                'is_refundable' => $isRefundable ?? true,
                'deadline' => $deadline,
                'deadline_formatted' => self::formatDeadline($deadline),
                'reason' => 'Cancellation deadline has expired.',
                'policy_summary' => self::tboPolicySummary($source),
            ]);
        }

        return array_merge($base, [
            'can_cancel' => true,
            'is_refundable' => $isRefundable ?? true,
            'flow' => 'tbo_direct',
            'policy_summary' => self::tboPolicySummary($source),
            'deadline' => $deadline,
            'deadline_formatted' => self::formatDeadline($deadline),
        ]);
    }

    private static function flightIsNonRefundable(B2bFlightBooking $booking): ?bool
    {
        $itinerary = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];

        if (array_key_exists('non_refundable', $itinerary)) {
            return (bool) ($itinerary['non_refundable'] ?? false);
        }

        $passengerFare = $itinerary['passenger_fare'] ?? null;
        if (is_array($passengerFare) && array_key_exists('nonRefundable', $passengerFare)) {
            return (bool) ($passengerFare['nonRefundable'] ?? false);
        }

        $passengerFare = data_get(
            $booking->search_response,
            'groupedItineraryResponse.itineraryGroups.0.itineraries.0.pricingInformation.0.fare.passengerInfoList.0.passengerInfo'
        );
        if (is_array($passengerFare) && array_key_exists('nonRefundable', $passengerFare)) {
            return (bool) ($passengerFare['nonRefundable'] ?? false);
        }

        return null;
    }

    private static function yalagoStoredRefundable(B2bHotelBooking $booking): ?bool
    {
        $selectedRooms = is_array($booking->selected_rooms) ? $booking->selected_rooms : [];

        foreach ($selectedRooms as $room) {
            if (!is_array($room)) {
                continue;
            }

            if (array_key_exists('non_refundable', $room)) {
                return empty($room['non_refundable']);
            }
        }

        $response = is_array($booking->booking_response) ? $booking->booking_response : [];
        $label = self::yalagoRefundabilityLabelFromSource($response, $booking);

        return match ($label) {
            'Non-refundable' => false,
            'Refundable' => true,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private static function yalagoRefundabilityLabelFromSource(array $source, B2bHotelBooking $booking): ?string
    {
        foreach (data_get($source, 'Rooms', []) as $room) {
            if (!is_array($room)) {
                continue;
            }

            if (!empty($room['NonRefundable'])) {
                return 'Non-refundable';
            }

            $board = $room['Board'] ?? null;
            if (is_array($board) && !empty($board['NonRefundable'])) {
                return 'Non-refundable';
            }

            if (is_array($board) && array_key_exists('NonRefundable', $board) && empty($board['NonRefundable'])) {
                return 'Refundable';
            }
        }

        if (!empty($source['NonRefundable'])) {
            return 'Non-refundable';
        }

        $selectedRooms = is_array($booking->selected_rooms) ? $booking->selected_rooms : [];
        foreach ($selectedRooms as $room) {
            if (!is_array($room)) {
                continue;
            }

            if (array_key_exists('non_refundable', $room)) {
                return !empty($room['non_refundable']) ? 'Non-refundable' : 'Refundable';
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $charges
     * @return array{deadline: Carbon|null}|null
     */
    private static function yalagoCurrentChargeWindow(array $charges): ?array
    {
        $policy = $charges['CancellationPolicyStatic'][0] ?? null;
        if (!is_array($policy)) {
            return ['deadline' => null];
        }

        $chargesList = $policy['CancellationCharges'] ?? [];
        if ($chargesList === []) {
            return ['deadline' => null];
        }

        $today = now()->toDateString();

        foreach ($chargesList as $charge) {
            if (!is_array($charge)) {
                continue;
            }

            $expiryRaw = $charge['ExpiryDate'] ?? $charge['ExpiryDateUTC'] ?? null;
            if ($expiryRaw === null) {
                continue;
            }

            $expiry = substr((string) $expiryRaw, 0, 10);
            if ($today <= $expiry) {
                return ['deadline' => self::parseDate($expiryRaw)];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $charges
     */
    private static function yalagoLastCancellationDeadline(array $charges): ?Carbon
    {
        $policy = $charges['CancellationPolicyStatic'][0] ?? null;
        if (!is_array($policy)) {
            return null;
        }

        $latest = null;

        foreach ($policy['CancellationCharges'] ?? [] as $charge) {
            if (!is_array($charge)) {
                continue;
            }

            $expiryRaw = $charge['ExpiryDate'] ?? $charge['ExpiryDateUTC'] ?? null;
            $parsed = self::parseDate($expiryRaw);

            if ($parsed === null) {
                continue;
            }

            if ($latest === null || $parsed->greaterThan($latest)) {
                $latest = $parsed;
            }
        }

        return $latest;
    }

    /**
     * @param  array<string, mixed>  $charges
     */
    private static function yalagoPolicySummaryFromCharges(array $charges): ?string
    {
        $lines = [];
        $policy = $charges['CancellationPolicyStatic'][0] ?? null;

        if (!is_array($policy)) {
            return null;
        }

        foreach ($policy['CancellationCharges'] ?? [] as $charge) {
            if (!is_array($charge)) {
                continue;
            }

            $amount = data_get($charge, 'Charge.Amount');
            $currency = data_get($charge, 'Charge.Currency', '');
            $expiryRaw = $charge['ExpiryDate'] ?? $charge['ExpiryDateUTC'] ?? null;

            if ($amount === null || $expiryRaw === null) {
                continue;
            }

            $date = self::formatDeadline(self::parseDate($expiryRaw)) ?? (string) $expiryRaw;
            $lines[] = ((float) $amount <= 0)
                ? "Free cancellation until {$date}"
                : trim("{$currency} " . number_format((float) $amount, 2) . " charge until {$date}");
        }

        return $lines === [] ? null : implode(' · ', $lines);
    }

    /**
     * @param  array<string, mixed>|null  $tboDetail
     * @return array<string, mixed>
     */
    private static function tboPolicySource(B2bHotelBooking $booking, ?array $tboDetail): array
    {
        if (is_array($tboDetail) && $tboDetail !== []) {
            $detailBody = data_get($tboDetail, 'response');
            if (is_array($detailBody)) {
                $bookingDetail = data_get($detailBody, 'BookingDetail');
                if (is_array($bookingDetail)) {
                    return $bookingDetail;
                }

                return $detailBody;
            }
        }

        return is_array($booking->booking_response) ? $booking->booking_response : [];
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private static function tboCancellationDeadline(array $source): ?Carbon
    {
        $deadlineRaw = data_get($source, 'HotelCancelPolicies.LastCancellationDeadline')
            ?? data_get($source, 'CancelPolicies.LastCancellationDeadline')
            ?? data_get($source, 'LastCancellationDeadline');

        if ($deadlineRaw !== null && $deadlineRaw !== '') {
            return self::parseDate($deadlineRaw);
        }

        $policies = data_get($source, 'HotelCancelPolicies')
            ?? data_get($source, 'CancelPolicies');

        if (!is_array($policies)) {
            return null;
        }

        if (array_is_list($policies)) {
            $latest = null;

            foreach ($policies as $policy) {
                if (!is_array($policy)) {
                    continue;
                }

                $toDate = $policy['ToDate'] ?? $policy['ToDateTime'] ?? $policy['EndDate'] ?? null;
                $fromDate = $policy['FromDate'] ?? $policy['FromDateTime'] ?? $policy['StartDate'] ?? null;
                $candidate = self::parseDate($toDate ?? $fromDate);

                if ($candidate === null) {
                    continue;
                }

                if ($latest === null || $candidate->greaterThan($latest)) {
                    $latest = $candidate;
                }
            }

            return $latest;
        }

        foreach (['LastCancellationDeadline', 'AutoCancellationText', 'CancelPolicy'] as $key) {
            $value = $policies[$key] ?? null;
            if ($value !== null && $value !== '') {
                $parsed = self::parseDate($value);

                return $parsed ?? (is_string($value) ? null : null);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private static function tboPolicySummary(array $source): ?string
    {
        $cancelPolicy = data_get($source, 'HotelCancelPolicies') ?? data_get($source, 'CancelPolicies');

        if (is_string($cancelPolicy) && trim($cancelPolicy) !== '') {
            return trim($cancelPolicy);
        }

        if (is_array($cancelPolicy)) {
            $text = data_get($cancelPolicy, 'AutoCancellationText')
                ?? data_get($cancelPolicy, 'CancelPolicy')
                ?? data_get($cancelPolicy, 'DefaultPolicy');

            if (is_string($text) && trim($text) !== '') {
                return trim($text);
            }

            if (array_is_list($cancelPolicy)) {
                $lines = [];

                foreach ($cancelPolicy as $policy) {
                    if (!is_array($policy)) {
                        continue;
                    }

                    $from = self::formatDeadline(self::parseDate(
                        $policy['FromDate'] ?? $policy['FromDateTime'] ?? null
                    ));
                    $to = self::formatDeadline(self::parseDate(
                        $policy['ToDate'] ?? $policy['ToDateTime'] ?? null
                    ));
                    $charge = $policy['CancellationCharge'] ?? $policy['Charge'] ?? null;

                    if ($from && $to && $charge !== null) {
                        $lines[] = "Charge {$charge}% from {$from} to {$to}";
                    } elseif ($to && $charge !== null) {
                        $lines[] = "Charge {$charge}% until {$to}";
                    }
                }

                if ($lines !== []) {
                    return implode(' · ', $lines);
                }
            }

            $deadline = self::tboCancellationDeadline($source);

            if ($deadline !== null) {
                return 'Last cancellation deadline: ' . self::formatDeadline($deadline);
            }
        }

        return HotelRefundPresentation::tboSummary(
            HotelRefundPresentation::tboRefundMetaFromBookingResponse($source)['is_refundable']
        );
    }

    private static function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function formatDeadline(?Carbon $deadline): ?string
    {
        return $deadline?->format('d M Y');
    }
}
