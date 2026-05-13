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
        return redirect()->route('user.bookings.flights');
    }

    public function flights(Request $request)
    {
        $query = B2bFlightBooking::where('b2b_vendor_id', Auth::id())
            ->orderByDesc('created_at');

        $status = $request->query('status', 'all');
        if ($status && $status !== 'all') {
            if ($status === 'confirmed') {
                $query->whereIn('booking_status', ['confirmed', 'completed']);
            } else {
                $query->where('booking_status', $status);
            }
        }

        $search = $request->query('search', '');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                  ->orWhere('sabre_record_locator', 'like', "%{$search}%")
                  ->orWhere('from_airport', 'like', "%{$search}%")
                  ->orWhere('to_airport', 'like', "%{$search}%");
            });
        }

        $flightBookings = $query->paginate(10)->withQueryString();
        $counts         = $this->bookingCounts();

        return view('user.bookings.flights', compact('flightBookings', 'counts', 'status', 'search'));
    }

    public function flightDetail(int $id)
    {
        $booking = B2bFlightBooking::where('b2b_vendor_id', Auth::id())->findOrFail($id);
        $counts  = $this->bookingCounts();

        return view('user.bookings.flight-detail', compact('booking', 'counts'));
    }

    public function hotels(Request $request)
    {
        $query = B2bHotelBooking::where('b2b_vendor_id', Auth::id())
            ->orderByDesc('created_at');

        $status = $request->query('status', 'all');
        if ($status && $status !== 'all') {
            if ($status === 'confirmed') {
                $query->whereIn('booking_status', ['confirmed', 'completed']);
            } else {
                $query->where('booking_status', $status);
            }
        }

        $search = $request->query('search', '');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                  ->orWhere('hotel_name', 'like', "%{$search}%");
            });
        }

        $hotelBookings = $query->paginate(10)->withQueryString();
        $counts        = $this->bookingCounts();

        return view('user.bookings.hotels', compact('hotelBookings', 'counts', 'status', 'search'));
    }

    public function hotelDetail(int $id)
    {
        $booking = B2bHotelBooking::where('b2b_vendor_id', Auth::id())->findOrFail($id);
        $counts  = $this->bookingCounts();

        return view('user.bookings.hotel-detail', compact('booking', 'counts'));
    }

    private function bookingCounts(): array
    {
        $vid = Auth::id();
        return [
            'flights' => B2bFlightBooking::where('b2b_vendor_id', $vid)->count(),
            'hotels'  => B2bHotelBooking::where('b2b_vendor_id', $vid)->count(),
        ];
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
                ->route('user.bookings.hotels')
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
                ->route('user.bookings.hotels')
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

        if ($booking->booking_status !== 'hold') {
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

            $pnr = trim((string) ($booking->sabre_record_locator ?? ''));
            $successMsg = $pnr !== ''
                ? 'Hold released for booking #' . $booking->booking_number . '. PNR ' . $pnr . ' was cancelled on Sabre.'
                : 'Hold released for booking #' . $booking->booking_number . '.';

            return redirect()
                ->route('user.bookings.flights')
                ->with('notify_success', $successMsg);
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
                ->route('user.bookings.flights')
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
