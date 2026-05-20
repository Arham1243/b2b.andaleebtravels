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
        ]);

        $bookingModel = B2bHotelBooking::findOrFail($booking);
        $before = $bookingModel->only(['payment_status', 'booking_status']);

        $ledger = DB::transaction(function () use ($bookingModel, $validated, $before) {
            $bookingModel->update($validated);

            return $this->bookingWalletRefundService->processAfterAdminStatusUpdate(
                $bookingModel->fresh(),
                $before,
                $validated
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
        ]);

        $bookingModel = B2bFlightBooking::findOrFail($booking);
        $before = $bookingModel->only(['payment_status', 'booking_status', 'ticket_status']);

        $ledger = DB::transaction(function () use ($bookingModel, $validated, $before) {
            $bookingModel->update($validated);

            return $this->bookingWalletRefundService->processAfterAdminStatusUpdate(
                $bookingModel->fresh(),
                $before,
                $validated
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
                $cancelResponse = $hotelService->cancelTboBooking($bookingModel);
                $type = 'hotel_tbo_cancel';
            } else {
                $charges = $hotelService->getCancellationCharges($bookingModel);
                $cancelResponse = $hotelService->cancelYalagoBooking($bookingModel, $charges);
                $type = 'hotel_yalago_cancel';
            }

            $before = $bookingModel->only(['payment_status', 'booking_status']);

            $bookingModel->update([
                'booking_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => 'admin',
                'cancel_response' => BookingCancellationRecorder::envelope($type, $cancelResponse, 'admin'),
            ]);

            $ledger = $this->bookingWalletRefundService->processAfterAdminCancellation(
                $bookingModel->fresh(),
                $before
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

        if ($bookingModel->booking_status !== 'hold') {
            return redirect()->back()->with('notify_error', 'This action is only available for held bookings.');
        }

        DB::beginTransaction();

        try {
            $cancelResponse = [];
            $cancellationType = 'flight_hold_release_local';

            if (! empty($bookingModel->sabre_record_locator)) {
                $cancelResponse = $flightService->cancelSabreBooking($bookingModel);
                $cancellationType = 'flight_hold_release_sabre';
            } else {
                $cancelResponse = [
                    'note' => 'No Sabre PNR on record; hold cleared locally only.',
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

            $pnr = trim((string) ($bookingModel->sabre_record_locator ?? ''));
            $successMsg = $pnr !== ''
                ? 'Hold released for booking #' . $bookingModel->booking_number . '. PNR ' . $pnr . ' was cancelled on Sabre.'
                : 'Hold released for booking #' . $bookingModel->booking_number . '.';

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
            $cancelResponse = $flightService->cancelSabreBooking($bookingModel);

            $bookingModel->update([
                'booking_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => 'admin',
                'cancel_response' => BookingCancellationRecorder::envelope('flight_sabre_cancel', $cancelResponse, 'admin'),
            ]);

            DB::commit();

            app(BookingCancellationNotifier::class)->notifyFlightCancelled($bookingModel->fresh());

            return redirect()->back()->with('notify_success', 'Flight booking cancelled successfully.');
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
