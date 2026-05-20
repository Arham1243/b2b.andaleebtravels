<?php

namespace App\Services;

use App\Models\B2bFlightBooking;
use App\Models\B2bHotelBooking;
use App\Models\B2bVendor;
use App\Models\B2bWalletLedger;
use App\Support\WalletLedgerDescription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingWalletRefundService
{
    /**
     * Credit vendor wallet when an admin marks a paid booking as refunded.
     *
     * @param  array<string, string>  $before  payment_status, booking_status, and ticket_status (flights only)
     * @param  array<string, string>  $after   validated status fields from the admin form
     */
    public function processAfterAdminStatusUpdate(
        Model $booking,
        array $before,
        array $after,
        bool $skipWalletRefund = false
    ): ?B2bWalletLedger {
        if (! $booking instanceof B2bHotelBooking && ! $booking instanceof B2bFlightBooking) {
            return null;
        }

        if ($skipWalletRefund) {
            return null;
        }

        if (! $this->hadPaymentCollected($booking, $before)) {
            return null;
        }

        if (! $this->isNewlyRefunded($before, $after, $booking instanceof B2bFlightBooking)) {
            return null;
        }

        return $this->creditBookingRefund($booking);
    }

    /**
     * Credit wallet when a refundable booking is cancelled and payment was collected.
     *
     * @param  array<string, string>  $before
     */
    public function processAfterCancellation(
        B2bHotelBooking|B2bFlightBooking $booking,
        array $before,
        bool $isRefundable
    ): ?B2bWalletLedger {
        if (! $isRefundable) {
            return null;
        }

        if (($before['booking_status'] ?? '') === 'cancelled') {
            return null;
        }

        if (! $this->hadPaymentCollected($booking, $before)) {
            return null;
        }

        return $this->creditBookingRefund($booking);
    }

    /**
     * @param  array<string, string>  $before
     */
    public function paymentStatusAfterCancellationRefund(array $before, bool $isRefundable): ?string
    {
        if (! $isRefundable) {
            return null;
        }

        if (($before['payment_status'] ?? '') === 'paid') {
            return 'refunded';
        }

        return null;
    }

    public function walletRefundAlreadyCredited(B2bHotelBooking|B2bFlightBooking $booking): bool
    {
        return B2bWalletLedger::refundCreditExists($booking::class, $booking->id);
    }

    /**
     * @param  array<string, string>  $before
     */
    private function hadPaymentCollected(Model $booking, array $before): bool
    {
        $paymentStatus = (string) ($before['payment_status'] ?? '');

        if ($paymentStatus === 'paid') {
            return true;
        }

        if ($paymentStatus === 'refunded') {
            return false;
        }

        if (B2bWalletLedger::query()
            ->where('reference_type', $booking::class)
            ->where('reference_id', $booking->id)
            ->where('type', 'debit')
            ->exists()) {
            return true;
        }

        $bookingStatus = (string) ($before['booking_status'] ?? '');

        return in_array($bookingStatus, ['confirmed', 'completed', 'hold'], true)
            && ! in_array($paymentStatus, ['pending', 'failed', ''], true)
            && (float) $booking->total_amount > 0.001;
    }

    /**
     * @param  array<string, string>  $before
     * @param  array<string, string>  $after
     */
    private function isNewlyRefunded(array $before, array $after, bool $isFlight): bool
    {
        if (($after['payment_status'] ?? '') === 'refunded' && ($before['payment_status'] ?? '') !== 'refunded') {
            return true;
        }

        if (($after['booking_status'] ?? '') === 'refunded' && ($before['booking_status'] ?? '') !== 'refunded') {
            return true;
        }

        if ($isFlight
            && ($after['ticket_status'] ?? '') === 'refunded'
            && ($before['ticket_status'] ?? '') !== 'refunded') {
            return true;
        }

        return false;
    }

    public function creditBookingRefund(B2bHotelBooking|B2bFlightBooking $booking): ?B2bWalletLedger
    {
        $vendorId = (int) $booking->b2b_vendor_id;
        if ($vendorId <= 0) {
            return null;
        }

        $amount = round((float) $booking->total_amount, 2);
        if ($amount <= 0.001) {
            return null;
        }

        $referenceType = $booking::class;
        $description = $booking instanceof B2bFlightBooking
            ? WalletLedgerDescription::creditFlightRefund($booking)
            : WalletLedgerDescription::creditHotelRefund($booking);

        try {
            return DB::transaction(function () use ($vendorId, $amount, $description, $referenceType, $booking) {
                B2bVendor::query()->whereKey($vendorId)->lockForUpdate()->firstOrFail();

                if (B2bWalletLedger::refundCreditExists($referenceType, $booking->id)) {
                    return null;
                }

                return B2bWalletLedger::recordCredit(
                    $vendorId,
                    $amount,
                    $description,
                    $referenceType,
                    $booking->id
                );
            });
        } catch (\Throwable $e) {
            Log::error('Booking wallet refund credit failed', [
                'booking_type' => $referenceType,
                'booking_id' => $booking->id,
                'vendor_id' => $vendorId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
