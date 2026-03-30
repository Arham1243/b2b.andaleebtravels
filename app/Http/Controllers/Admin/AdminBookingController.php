<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2bFlightBooking;
use App\Models\B2bHotelBooking;
use App\Services\FlightService;
use App\Services\HotelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminBookingController extends Controller
{
    public function updateHotelStatus(Request $request, int $booking)
    {
        $validated = $request->validate([
            'booking_status' => 'required|in:pending,confirmed,cancelled,completed,refunded,failed',
            'payment_status' => 'required|in:pending,paid,failed,refunded',
        ]);

        $bookingModel = B2bHotelBooking::findOrFail($booking);
        $bookingModel->update($validated);

        return redirect()->back()->with('notify_success', 'Hotel booking status updated.');
    }

    public function updateFlightStatus(Request $request, int $booking)
    {
        $validated = $request->validate([
            'booking_status' => 'required|in:pending,confirmed,cancelled,completed,refunded,failed',
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'ticket_status' => 'required|in:pending,issued,failed,refunded',
        ]);

        $bookingModel = B2bFlightBooking::findOrFail($booking);
        $bookingModel->update($validated);

        return redirect()->back()->with('notify_success', 'Flight booking status updated.');
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
            } else {
                $charges = $hotelService->getCancellationCharges($bookingModel);
                $cancelResponse = $hotelService->cancelYalagoBooking($bookingModel, $charges);
            }

            $bookingModel->update([
                'booking_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => 'admin',
                'cancel_response' => $cancelResponse,
            ]);

            DB::commit();

            return redirect()->back()->with('notify_success', 'Hotel booking cancelled successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Admin hotel cancellation failed', [
                'booking_id' => $bookingModel->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('notify_error', 'Unable to cancel booking: ' . $e->getMessage());
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
                'cancel_response' => $cancelResponse,
            ]);

            DB::commit();

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
