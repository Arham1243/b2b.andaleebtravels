<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2bFlightBooking;
use App\Models\B2bVendor;
use App\Services\FlightService;
use App\Support\SupplierFlightBookingDetailsPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminFlightBookingController extends Controller
{
    public function index(Request $request)
    {
        $filterVendorId = $request->input('vendor_id');
        $filterVendor = null;

        if ($filterVendorId !== null && $filterVendorId !== '' && ctype_digit((string) $filterVendorId)) {
            $filterVendor = B2bVendor::find((int) $filterVendorId);
        }

        $bookings = B2bFlightBooking::query()
            ->with('vendor')
            ->when($filterVendor !== null, fn ($q) => $q->where('b2b_vendor_id', $filterVendor->id))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.flight-bookings.list', compact('bookings', 'filterVendor'))
            ->with('title', 'Flight Bookings');
    }

    public function show(int $id, FlightService $flightService)
    {
        $booking = B2bFlightBooking::with('vendor')->findOrFail($id);

        $liveFetch = null;
        if (! empty($booking->sabre_record_locator)) {
            $liveFetch = $flightService->fetchLiveSabreBookingDetails($booking);

            if (empty($liveFetch['ok'])) {
                Log::warning('Supplier booking detail lookup failed (admin flight booking show)', [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'error' => $liveFetch['error'] ?? null,
                ]);
            }
        }

        $supplierBookingDetails = SupplierFlightBookingDetailsPresenter::present($booking, $liveFetch);

        return view('admin.flight-bookings.show', compact('booking', 'supplierBookingDetails'))
            ->with('title', 'Booking ' . $booking->booking_number);
    }
}
