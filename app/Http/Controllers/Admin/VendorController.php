<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VendorInviteMail;
use App\Models\B2bVendor;
use App\Traits\UploadImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class VendorController extends Controller
{
    use UploadImageTrait;
    public function index()
    {
        $vendors = B2bVendor::latest()->get();
        return view('admin.vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('admin.vendors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'travel_agency' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:b2b_vendors,email|max:255',
            'designation' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:b2b_vendors,username',
            'trade_license_number' => 'required|string|max:255',
            'trade_license_expiry' => 'required|date',
            'agency_logo' => 'nullable|image|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        $plainPassword = '12345678';

        $agencyLogo = $request->hasFile('agency_logo')
            ? $this->uploadImage($request->file('agency_logo'), 'Vendors/AgencyLogo')
            : null;

        $vendor = B2bVendor::create([
            'name' => $validated['travel_agency'],
            'travel_agency' => $validated['travel_agency'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'designation' => $validated['designation'],
            'username' => $validated['username'],
            'trade_license_number' => $validated['trade_license_number'],
            'trade_license_expiry' => $validated['trade_license_expiry'],
            'agency_logo' => $agencyLogo,
            'agent_code' => $this->generateUniqueAgentCode(),
            'password' => Hash::make($plainPassword),
            'status' => $validated['status'],
        ]);

        try {
            Mail::to($vendor->email)->send(new VendorInviteMail($vendor, $plainPassword));
        } catch (\Exception $e) {
            Log::error('Failed to send vendor invite email: ' . $e->getMessage());
        }

        return redirect()->route('admin.vendors.index')
            ->with('notify_success', 'Vendor created successfully! Invite email sent.');
    }

    public function show(B2bVendor $vendor)
    {
        $vendor->load('parentVendor');

        $walletLedger = $vendor->walletLedger()->with('reference')->latest()->get();
        $hotelBookings = $vendor->hotelBookings()->latest()->get();
        $flightBookings = $vendor->flightBookings()->latest()->get();
        $subAgents = $vendor->subAgents()->latest()->get();

        $stats = [
            'hotel_bookings'  => $hotelBookings->count(),
            'flight_bookings' => $flightBookings->count(),
            'total_spent'     => $hotelBookings->sum('total_amount') + $flightBookings->sum('total_amount'),
            'ledger_entries'  => $walletLedger->count(),
            'sub_agents'      => $subAgents->count(),
        ];

        return view('admin.vendors.show', compact('vendor', 'walletLedger', 'hotelBookings', 'flightBookings', 'subAgents', 'stats'));
    }

    public function changeStatus(B2bVendor $vendor)
    {
        $vendor->update([
            'status' => $vendor->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->back()->with('notify_success', 'Vendor status changed successfully!');
    }

    public function destroy(B2bVendor $vendor)
    {
        if ($vendor->hasAssociatedData()) {
            return redirect()->back()->with(
                'notify_error',
                'Cannot delete this vendor. This account has existing bookings or related data.'
            );
        }

        $vendor->delete();

        return redirect()->route('admin.vendors.index')->with('notify_success', 'Vendor deleted successfully!');
    }

    private function generateUniqueAgentCode(): string
    {
        do {
            $code = 'AT' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while (B2bVendor::where('agent_code', $code)->exists());

        return $code;
    }
}
