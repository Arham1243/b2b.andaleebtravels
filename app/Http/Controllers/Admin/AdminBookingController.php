<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2bFlightBooking;
use App\Models\B2bHotelBooking;
use App\Models\B2bWalletLedger;
use App\Services\BookingCancellationNotifier;
use App\Services\BookingCancellationRecorder;
use App\Services\BookingWalletRefundService;
use App\Services\FlightService;
use App\Services\HotelService;
use App\Support\BookingCancellationEligibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminBookingController extends Controller
{
    public function __construct(
        private readonly BookingWalletRefundService $bookingWalletRefundService,
    ) {}

    public function updateHotelStatus(Request $request, int $booking)
    {
        $validated = $request->validate([
            'booking_status' => 'required|in:pending,confirmed,cancelled,completed,refunded,failed',
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'skip_wallet_refund' => 'sometimes|boolean',
        ]);

        $bookingModel = B2bHotelBooking::findOrFail($booking);
        $before = $bookingModel->only(['payment_status', 'booking_status']);
        $skipWalletRefund = $request->boolean('skip_wallet_refund');

        $ledger = DB::transaction(function () use ($bookingModel, $validated, $before, $skipWalletRefund) {
            $bookingModel->update(collect($validated)->except('skip_wallet_refund')->all());

            return $this->bookingWalletRefundService->processAfterAdminStatusUpdate(
                $bookingModel->fresh(),
                $before,
                $validated,
                $skipWalletRefund
            );
        });

        return redirect()->back()->with(
            'notify_success',
            $this->statusUpdateMessage('Hotel booking status updated.', $ledger)
        );
    }

    public function updateFlightStatus(Request $request, int $booking)
    {
        $validated = $request->validate([
            'booking_status' => 'required|in:pending,confirmed,hold,cancelled,completed,refunded,failed',
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'ticket_status' => 'required|in:pending,issued,failed,refunded',
            'skip_wallet_refund' => 'sometimes|boolean',
        ]);

        $bookingModel = B2bFlightBooking::findOrFail($booking);
        $before = $bookingModel->only(['payment_status', 'booking_status', 'ticket_status']);
        $skipWalletRefund = $request->boolean('skip_wallet_refund');

        $ledger = DB::transaction(function () use ($bookingModel, $validated, $before, $skipWalletRefund) {
            $bookingModel->update(collect($validated)->except('skip_wallet_refund')->all());

            return $this->bookingWalletRefundService->processAfterAdminStatusUpdate(
                $bookingModel->fresh(),
                $before,
                $validated,
                $skipWalletRefund
            );
        });

        return redirect()->back()->with(
            'notify_success',
            $this->statusUpdateMessage('Flight booking status updated.', $ledger)
        );
    }

    private function statusUpdateMessage(string $base, ?B2bWalletLedger $ledger): string
    {
        if ($ledger === null) {
            return $base;
        }

        return $base . ' Vendor wallet credited ' . number_format((float) $ledger->amount, 2) . ' AED.';
    }

    public function cancelHotelBooking(int $booking, HotelService $hotelService)
    {
        $bookingModel = B2bHotelBooking::findOrFail($booking);

        if ($bookingModel->booking_status === 'cancelled') {
            return redirect()->back()->with('notify_error', 'Booking already cancelled.');
        }

        DB::beginTransaction();

        try {
            if (($bookingModel->supplier ?? '') === 'tbo') {
                BookingCancellationEligibility::assertTboCanCancel($bookingModel);
                $cancelResponse = $hotelService->cancelTboBooking($bookingModel);
                $type = 'hotel_tbo_cancel';
                $isRefundable = BookingCancellationEligibility::hotelIsRefundableForWalletRefund($bookingModel);
            } else {
                $charges = $hotelService->getCancellationCharges($bookingModel);
                BookingCancellationEligibility::assertYalagoCanCancel($bookingModel, $charges);
                $cancelResponse = $hotelService->cancelYalagoBooking($bookingModel, $charges);
                $type = 'hotel_yalago_cancel';
                $isRefundable = BookingCancellationEligibility::hotelIsRefundableForWalletRefund($bookingModel, $charges);
            }

            $before = $bookingModel->only(['payment_status', 'booking_status']);

            $update = [
                'booking_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => 'admin',
                'cancel_response' => BookingCancellationRecorder::envelope($type, $cancelResponse, 'admin'),
            ];

            if ($refundedPayment = $this->bookingWalletRefundService->paymentStatusAfterCancellationRefund($before, $isRefundable)) {
                $update['payment_status'] = $refundedPayment;
            }

            $bookingModel->update($update);

            $ledger = $this->bookingWalletRefundService->processAfterCancellation(
                $bookingModel->fresh(),
                $before,
                $isRefundable
            );

            DB::commit();

            app(BookingCancellationNotifier::class)->notifyHotelCancelled($bookingModel->fresh());

            return redirect()->back()->with(
                'notify_success',
                $this->statusUpdateMessage('Hotel booking cancelled successfully.', $ledger)
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Admin hotel cancellation failed', [
                'booking_id' => $bookingModel->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('notify_error', 'Unable to cancel booking: ' . $e->getMessage());
        }
    }

    public function releaseFlightHold(int $booking, FlightService $flightService)
    {
        $bookingModel = B2bFlightBooking::findOrFail($booking);

        if ($bookingModel->booking_status === 'cancelled') {
            return redirect()->back()->with('notify_error', 'Booking is already cancelled.');
        }

        if ($bookingModel->booking_status !== 'hold' || $bookingModel->isPaid()) {
            return redirect()->back()->with('notify_error', 'This action is only available for held bookings.');
        }

        if ($bookingModel->isTravelport() && $bookingModel->hasAirlinePnr() && $bookingModel->travelportUniversalLocator() === '') {
            return redirect()->back()->with('notify_error', 'Unable to release hold at the airline: Travelport cancel reference is missing. Please contact support.');
        }

        DB::beginTransaction();

        try {
            $cancelResponse = [];
            $cancellationType = 'flight_hold_release_local';

            if (! empty($bookingModel->sabre_record_locator)) {
                if ($bookingModel->isTravelport()) {
                    $cancelResponse = $flightService->cancelTravelportBooking($bookingModel);
                    $cancellationType = 'flight_hold_release_travelport';
                } else {
                    $cancelResponse = $flightService->cancelSabreBooking($bookingModel);
                    $cancellationType = 'flight_hold_release_sabre';
                }
            } else {
                $cancelResponse = [
                    'note' => 'No airline PNR on record; hold cleared locally only.',
                ];
            }

            $bookingModel->update([
                'booking_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => 'admin_release',
                'cancel_response' => BookingCancellationRecorder::envelope($cancellationType, $cancelResponse, 'admin_release'),
            ]);

            DB::commit();

            app(BookingCancellationNotifier::class)->notifyFlightHoldReleased($bookingModel->fresh());

            $successMsg = flightHoldReleaseSuccessMessage($bookingModel);

            return redirect()->back()->with('notify_success', $successMsg);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Admin flight hold release failed', [
                'booking_id' => $bookingModel->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('notify_error', 'Unable to release hold: ' . $e->getMessage());
        }
    }

    public function cancelFlightBooking(int $booking, FlightService $flightService)
    {
        $bookingModel = B2bFlightBooking::findOrFail($booking);

        if ($bookingModel->booking_status === 'cancelled') {
            return redirect()->back()->with('notify_error', 'Booking already cancelled.');
        }

        DB::beginTransaction();

        try {
            BookingCancellationEligibility::assertFlightCanCancel($bookingModel);
            $cancelResponse = $bookingModel->isTravelport()
                ? $flightService->cancelTravelportBooking($bookingModel)
                : $flightService->cancelSabreBooking($bookingModel);

            $before = $bookingModel->only(['payment_status', 'booking_status']);
            $isRefundable = BookingCancellationEligibility::flightIsRefundableForWalletRefund($bookingModel);

            $update = [
                'booking_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => 'admin',
                'cancel_response' => BookingCancellationRecorder::envelope(
                    $bookingModel->isTravelport() ? 'flight_travelport_cancel' : 'flight_sabre_cancel',
                    $cancelResponse,
                    'admin'
                ),
            ];

            if ($refundedPayment = $this->bookingWalletRefundService->paymentStatusAfterCancellationRefund($before, $isRefundable)) {
                $update['payment_status'] = $refundedPayment;
            }

            $bookingModel->update($update);

            $ledger = $this->bookingWalletRefundService->processAfterCancellation(
                $bookingModel->fresh(),
                $before,
                $isRefundable
            );

            DB::commit();

            app(BookingCancellationNotifier::class)->notifyFlightCancelled($bookingModel->fresh());

            return redirect()->back()->with(
                'notify_success',
                $this->statusUpdateMessage('Flight booking cancelled successfully.', $ledger)
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Admin flight cancellation failed', [
                'booking_id' => $bookingModel->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('notify_error', 'Unable to cancel booking: ' . $e->getMessage());
        }
    }
}
