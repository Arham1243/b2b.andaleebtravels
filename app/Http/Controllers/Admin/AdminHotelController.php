<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2bVendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminHotelController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        $vendor = $this->getOrCreateAdminBookingVendor();

        Auth::guard('web')->login($vendor);
        $request->session()->put('admin_booking_vendor_id', $vendor->id);

        return redirect()->route('user.hotels.index');
    }

    private function getOrCreateAdminBookingVendor(): B2bVendor
    {
        $agentCode = 'ADMINBOOKING';
        $email = 'admin@andaleebtravels.com';
        $username = 'Andaleeb Travel Agency';

        $vendor = B2bVendor::where('agent_code', $agentCode)->first();
        if ($vendor) {
            return $vendor;
        }

        return B2bVendor::create([
            'name' => 'Andaleeb Travel Agency',
            'email' => $email,
            'username' => $username,
            'agent_code' => $agentCode,
            'password' => Hash::make('12345678'),
            'status' => 'active',
        ]);
    }
}
