<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2bFlightBooking;
use App\Models\B2bVendor;
use Illuminate\Http\Request;

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

    public function show(int $id)
    {
        $booking = B2bFlightBooking::with('vendor')->findOrFail($id);

        return view('admin.flight-bookings.show', compact('booking'))
            ->with('title', 'Booking ' . $booking->booking_number);
    }
}
