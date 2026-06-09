<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminBookingVendorResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminFlightController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        $vendor = AdminBookingVendorResolver::resolve(Auth::guard('admin')->user());

        Auth::guard('web')->login($vendor);
        $request->session()->put('admin_booking_vendor_id', $vendor->id);

        return redirect()->route('user.flights.index');
    }
}
