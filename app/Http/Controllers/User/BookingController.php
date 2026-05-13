<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\B2bFlightBooking;
use App\Models\B2bHotelBooking;
use App\Services\FlightService;
use App\Services\HotelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function index()
    {
        $vendorId = Auth::id();

        $hotelBookings = B2bHotelBooking::where('b2b_vendor_id', $vendorId)
            ->orderByDesc('created_at')
            ->get();

        $flightBookings = B2bFlightBooking::where('b2b_vendor_id', $vendorId)
            ->orderByDesc('created_at')
            ->get();

        return view('user.bookings.index', compact('hotelBookings', 'flightBookings'));
    }

    public function getCancellationCharges(Request $request, HotelService $hotelService)
    {
        $request->validate([
            'booking_id' => 'required|integer',
        ]);

        $booking = B2bHotelBooking::where('b2b_vendor_id', Auth::id())
            ->findOrFail($request->booking_id);

        $cancelUrl = route('user.bookings.hotels.cancel', $booking->id);

        $response = $hotelService->getCancellationCharges($booking);

        return view('user.bookings.partials.cancellation-charges', compact('booking', 'response', 'cancelUrl'));
    }

    public function cancelHotelBooking($id, HotelService $hotelService)
    {
        $booking = B2bHotelBooking::where('b2b_vendor_id', Auth::id())
            ->findOrFail($id);

        if ($booking->booking_status === 'cancelled') {
            return back()->with('notify_error', 'Booking already cancelled.');
        }

        if ($booking->payment_status !== 'paid') {
            return back()->with('notify_error', 'Only paid bookings can be cancelled.');
        }

        DB::beginTransaction();

        try {
            $charges = $hotelService->getCancellationCharges($booking);
            $cancelResponse = $hotelService->cancelYalagoBooking($booking, $charges);

            $booking->update([
                'booking_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => 'vendor',
                'cancel_response' => $cancelResponse,
            ]);

            DB::commit();

            return redirect()
                ->route('user.bookings.index')
                ->with('notify_success', 'Booking #' . $booking->booking_number . ' cancelled successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Hotel booking cancellation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('notify_error', 'Unable to cancel booking: ' . $e->getMessage());
        }
    }

    public function cancelTboBooking(Request $request, HotelService $hotelService)
    {
        $request->validate([
            'booking_id' => 'required|integer',
        ]);

        $booking = B2bHotelBooking::where('b2b_vendor_id', Auth::id())
            ->findOrFail($request->booking_id);

        if (($booking->supplier ?? '') !== 'tbo') {
            return back()->with('notify_error', 'Invalid booking supplier.');
        }

        if ($booking->booking_status === 'cancelled') {
            return back()->with('notify_error', 'Booking already cancelled.');
        }

        if ($booking->payment_status !== 'paid') {
            return back()->with('notify_error', 'Only paid bookings can be cancelled.');
        }

        DB::beginTransaction();

        try {
            $cancelResponse = $hotelService->cancelTboBooking($booking);

            $booking->update([
                'booking_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => 'vendor',
                'cancel_response' => $cancelResponse,
            ]);

            DB::commit();

            return redirect()
                ->route('user.bookings.index')
                ->with('notify_success', 'Booking #' . $booking->booking_number . ' cancelled successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('TBO booking cancellation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('notify_error', 'Unable to cancel booking: ' . $e->getMessage());
        }
    }

    public function releaseHold(int $id, FlightService $flightService)
    {
        $booking = B2bFlightBooking::where('b2b_vendor_id', Auth::id())
            ->findOrFail($id);

        if ($booking->booking_status === 'cancelled') {
            return back()->with('notify_error', 'Booking is already cancelled.');
        }

        if ($booking->payment_method !== 'hold') {
            return back()->with('notify_error', 'This action is only available for held bookings.');
        }

        DB::beginTransaction();

        try {
            $cancelResponse = [];
            if (!empty($booking->sabre_record_locator)) {
                $cancelResponse = $flightService->cancelSabreBooking($booking);
            }

            $booking->update([
                'booking_status' => 'cancelled',
                'cancelled_at'   => now(),
                'cancelled_by'   => 'vendor_release',
                'cancel_response' => $cancelResponse,
            ]);

            DB::commit();

            return redirect()
                ->route('user.bookings.index')
                ->with('notify_success', 'Hold booking #' . $booking->booking_number . ' released. PNR ' . $booking->sabre_record_locator . ' cancelled on Sabre.');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Hold release failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('notify_error', 'Unable to release hold: ' . $e->getMessage());
        }
    }

    public function cancelFlightBooking(int $id, FlightService $flightService)
    {
        $booking = B2bFlightBooking::where('b2b_vendor_id', Auth::id())
            ->findOrFail($id);

        if ($booking->booking_status === 'cancelled') {
            return back()->with('notify_error', 'Booking already cancelled.');
        }

        if ($booking->payment_status !== 'paid') {
            return back()->with('notify_error', 'Only paid bookings can be cancelled.');
        }

        DB::beginTransaction();

        try {
            $cancelResponse = $flightService->cancelSabreBooking($booking);

            $booking->update([
                'booking_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => 'vendor',
                'cancel_response' => $cancelResponse,
            ]);

            DB::commit();

            return redirect()
                ->route('user.bookings.index')
                ->with('notify_success', 'Booking #' . $booking->booking_number . ' cancelled successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Flight booking cancellation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('notify_error', 'Unable to cancel booking: ' . $e->getMessage());
        }
    }
}
