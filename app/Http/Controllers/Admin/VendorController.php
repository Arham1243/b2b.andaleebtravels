<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VendorApprovedMail;
use App\Mail\VendorInviteMail;
use App\Models\B2bVendor;
use App\Traits\UploadImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    use UploadImageTrait;

    public function index()
    {
        $vendors = B2bVendor::approvedAgencies()->latest()->get();

        return view('admin.vendors.index', compact('vendors'));
    }

    public function pendingIndex()
    {
        $vendors = B2bVendor::pendingSignups()->latest()->get();

        return view('admin.vendors.pending.index', compact('vendors'));
    }

    public function pendingShow(B2bVendor $vendor)
    {
        if (!$vendor->isPendingApproval() || !$vendor->isAgencyAccount()) {
            return redirect()->route('admin.vendors.pending.index')
                ->with('notify_error', 'This signup request is no longer pending.');
        }

        return view('admin.vendors.pending.show', compact('vendor'));
    }

    public function approve(Request $request, B2bVendor $vendor)
    {
        if (!$vendor->isPendingApproval() || !$vendor->isAgencyAccount()) {
            return redirect()->route('admin.vendors.pending.index')
                ->with('notify_error', 'This signup request is no longer pending.');
        }

        $vendor->update(['status' => 'active']);

        try {
            Mail::to($vendor->email)->send(new VendorApprovedMail($vendor));
        } catch (\Exception $e) {
            Log::error('Failed to send vendor approval email: ' . $e->getMessage());
        }

        return redirect()->route('admin.vendors.pending.index')
            ->with('notify_success', 'Agency approved successfully. Login notification email sent.');
    }

    public function reject(Request $request, B2bVendor $vendor)
    {
        if (!$vendor->isPendingApproval() || !$vendor->isAgencyAccount()) {
            return redirect()->route('admin.vendors.pending.index')
                ->with('notify_error', 'This signup request is no longer pending.');
        }

        if ($vendor->agency_logo) {
            Storage::disk('public')->delete($vendor->agency_logo);
        }

        $vendor->delete();

        return redirect()->route('admin.vendors.pending.index')
            ->with('notify_success', 'Signup request rejected and removed.');
    }

    public function create()
    {
        return view('admin.vendors.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateVendor($request);

        $plainPassword = $validated['password'] ?? '12345678';

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
        if ($vendor->isPendingApproval() && $vendor->isAgencyAccount()) {
            return redirect()->route('admin.vendors.pending.show', $vendor);
        }

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

    public function edit(B2bVendor $vendor)
    {
        if ($vendor->isPendingApproval() && $vendor->isAgencyAccount()) {
            return redirect()->route('admin.vendors.pending.show', $vendor);
        }

        return view('admin.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, B2bVendor $vendor)
    {
        if ($vendor->isPendingApproval() && $vendor->isAgencyAccount()) {
            return redirect()->route('admin.vendors.pending.show', $vendor)
                ->with('notify_error', 'Approve or reject this signup request before editing.');
        }

        $validated = $this->validateVendor($request, $vendor->id);

        $data = [
            'name' => $validated['travel_agency'],
            'travel_agency' => $validated['travel_agency'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'designation' => $validated['designation'],
            'username' => $validated['username'],
            'trade_license_number' => $validated['trade_license_number'],
            'trade_license_expiry' => $validated['trade_license_expiry'],
            'status' => $validated['status'],
        ];

        if ($request->hasFile('agency_logo')) {
            $data['agency_logo'] = $this->uploadImage(
                $request->file('agency_logo'),
                'Vendors/AgencyLogo',
                $vendor->agency_logo
            );
        }

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $vendor->update($data);

        return redirect()->route('admin.vendors.show', $vendor)
            ->with('notify_success', 'Vendor updated successfully.');
    }

    public function createSubAgent(B2bVendor $vendor)
    {
        if ($vendor->parent_vendor_id) {
            return redirect()->route('admin.vendors.show', $vendor->parent_vendor_id)
                ->with('notify_error', 'Sub agents cannot be added under another sub agent.');
        }

        return view('admin.vendors.sub-agents.create', compact('vendor'));
    }

    public function storeSubAgent(Request $request, B2bVendor $vendor)
    {
        if ($vendor->parent_vendor_id) {
            return redirect()->route('admin.vendors.show', $vendor->parent_vendor_id)
                ->with('notify_error', 'Sub agents cannot be added under another sub agent.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:b2b_vendors,email|max:255',
            'username' => 'required|string|max:255|unique:b2b_vendors,username',
            'password' => 'nullable|string|min:8',
            'status' => 'required|in:active,inactive',
        ]);

        $plainPassword = $validated['password'] ?? '12345678';

        $subAgent = B2bVendor::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'agent_code' => $this->generateUniqueAgentCode(),
            'password' => Hash::make($plainPassword),
            'status' => $validated['status'],
            'parent_vendor_id' => $vendor->id,
        ]);

        try {
            Mail::to($subAgent->email)->send(new VendorInviteMail($subAgent, $plainPassword));
        } catch (\Exception $e) {
            Log::error('Failed to send sub-agent invite email: ' . $e->getMessage());
        }

        return redirect()->route('admin.vendors.show', $vendor)
            ->with('notify_success', 'Sub agent created successfully! Invite email sent.');
    }

    public function changeStatus(B2bVendor $vendor)
    {
        if ($vendor->status === 'pending') {
            return redirect()->back()->with('notify_error', 'Pending signup requests must be approved or rejected from Signup Requests.');
        }

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

        if ($vendor->agency_logo) {
            Storage::disk('public')->delete($vendor->agency_logo);
        }

        $vendor->delete();

        return redirect()->route('admin.vendors.index')->with('notify_success', 'Vendor deleted successfully!');
    }

    private function validateVendor(Request $request, ?int $vendorId = null): array
    {
        return $request->validate([
            'travel_agency' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('b2b_vendors', 'email')->ignore($vendorId),
            ],
            'designation' => 'required|string|max:255',
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('b2b_vendors', 'username')->ignore($vendorId),
            ],
            'trade_license_number' => 'required|string|max:255',
            'trade_license_expiry' => 'required|date|after_or_equal:today',
            'agency_logo' => 'nullable|image|max:2048',
            'password' => $vendorId ? 'nullable|string|min:8' : 'nullable|string|min:8',
            'status' => 'required|in:active,inactive',
        ]);
    }

    private function generateUniqueAgentCode(): string
    {
        do {
            $code = 'AT' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while (B2bVendor::where('agent_code', $code)->exists());

        return $code;
    }
}
