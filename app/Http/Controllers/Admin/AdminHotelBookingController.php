<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2bHotelBooking;
use App\Models\B2bVendor;
use Illuminate\Http\Request;

class AdminHotelBookingController extends Controller
{
    public function index(Request $request)
    {
        $filterVendorId = $request->input('vendor_id');
        $filterVendor = null;

        if ($filterVendorId !== null && $filterVendorId !== '' && ctype_digit((string) $filterVendorId)) {
            $filterVendor = B2bVendor::find((int) $filterVendorId);
        }

        $query = B2bHotelBooking::query()
            ->with('vendor')
            ->when($filterVendor !== null, fn ($q) => $q->where('b2b_vendor_id', $filterVendor->id))
            ->orderByDesc('created_at');

        $status = $request->query('status', 'all');
        if ($status && $status !== 'all') {
            if ($status === 'confirmed') {
                $query->whereIn('booking_status', ['confirmed', 'completed']);
            } else {
                $query->where('booking_status', $status);
            }
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                    ->orWhere('hotel_name', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($vendorQuery) use ($search) {
                        $vendorQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $bookings = $query->paginate(15)->withQueryString();

        return view('admin.hotel-bookings.list', compact('bookings', 'filterVendor', 'status', 'search'))
            ->with('title', 'Hotel Bookings');
    }

    public function show(int $id)
    {
        $booking = B2bHotelBooking::with('vendor')->findOrFail($id);

        return view('admin.hotel-bookings.show', compact('booking'))
            ->with('title', 'Booking ' . $booking->booking_number);
    }
}
