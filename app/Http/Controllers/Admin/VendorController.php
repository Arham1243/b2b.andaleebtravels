<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VendorInviteMail;
use App\Models\B2bVendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VendorController extends Controller
{
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:b2b_vendors,email|max:255',
            'username' => 'required|string|max:255|unique:b2b_vendors,username',
            'agent_code' => 'required|string|max:255|unique:b2b_vendors,agent_code',
            'status' => 'required|in:active,inactive',
        ]);

        $plainPassword = Str::random(10);

        $vendor = B2bVendor::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'agent_code' => $validated['agent_code'],
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
        $walletLedger = $vendor->walletLedger()->latest()->get();
        $hotelBookings = $vendor->hotelBookings()->latest()->get();

        return view('admin.vendors.show', compact('vendor', 'walletLedger', 'hotelBookings'));
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
        $vendor->delete();
        return redirect()->route('admin.vendors.index')->with('notify_success', 'Vendor deleted successfully!');
    }
}
