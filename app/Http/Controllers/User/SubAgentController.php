<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Mail\VendorInviteMail;
use App\Models\B2bVendor;
use App\Support\B2bVendorValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubAgentController extends Controller
{
    public function index()
    {
        $subAgents = B2bVendor::where('parent_vendor_id', Auth::id())->latest()->get();
        $agencyAgentCode = Auth::user()->agent_code;

        return view('user.sub-agents.index', compact('subAgents', 'agencyAgentCode'));
    }

    public function create()
    {
        $agencyAgentCode = Auth::user()->agent_code;

        return view('user.sub-agents.create', compact('agencyAgentCode'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => B2bVendorValidation::emailRule(),
            'username' => B2bVendorValidation::usernameRule(),
            'password' => 'nullable|string|min:8',
            'status' => 'required|in:active,inactive',
        ], B2bVendorValidation::messages());

        $agencyAgentCode = trim((string) Auth::user()->agent_code);
        if ($agencyAgentCode === '') {
            return redirect()->back()
                ->withInput()
                ->with('notify_error', 'Your agency must have an agent code before adding sub agents.');
        }

        $plainPassword = $validated['password'] ?? '12345678';

        $vendor = B2bVendor::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'agent_code' => $agencyAgentCode,
            'password' => Hash::make($plainPassword),
            'status' => $validated['status'],
            'parent_vendor_id' => Auth::id(),
        ]);

        try {
            Mail::to($vendor->email)->send(new VendorInviteMail($vendor, $plainPassword));
        } catch (\Exception $e) {
            Log::error('Failed to send sub-agent invite email: ' . $e->getMessage());
        }

        return redirect()->route('user.sub-agents.index')
            ->with('notify_success', 'Sub agent created successfully! Invite email sent.');
    }

    public function edit(B2bVendor $subAgent)
    {
        $this->ensureOwnSubAgent($subAgent);
        $subAgent->load('parentVendor');
        $agencyAgentCode = $subAgent->loginAgentCode();

        return view('user.sub-agents.edit', compact('subAgent', 'agencyAgentCode'));
    }

    public function update(Request $request, B2bVendor $subAgent)
    {
        $this->ensureOwnSubAgent($subAgent);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => B2bVendorValidation::emailRule($subAgent->id),
            'username' => B2bVendorValidation::usernameRule($subAgent->id),
            'password' => 'nullable|string|min:8',
            'status' => 'required|in:active,inactive',
        ], B2bVendorValidation::messages());

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'status' => $validated['status'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $subAgent->update($data);

        return redirect()->route('user.sub-agents.index')
            ->with('notify_success', 'Sub agent updated successfully.');
    }

    public function destroy(B2bVendor $subAgent)
    {
        $this->ensureOwnSubAgent($subAgent);

        if ($subAgent->hasAssociatedData()) {
            return redirect()->back()->with(
                'notify_error',
                'Cannot delete this sub agent. This account has existing bookings or related data.'
            );
        }

        $subAgent->delete();

        return redirect()->route('user.sub-agents.index')
            ->with('notify_success', 'Sub agent deleted successfully.');
    }

    private function ensureOwnSubAgent(B2bVendor $subAgent): void
    {
        if ((int) $subAgent->parent_vendor_id !== (int) Auth::id()) {
            abort(404);
        }
    }
}
