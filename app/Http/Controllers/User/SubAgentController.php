<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Mail\VendorInviteMail;
use App\Models\B2bVendor;
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

        return view('user.sub-agents.index', compact('subAgents'));
    }

    public function create()
    {
        return view('user.sub-agents.create');
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

        $plainPassword = '12345678';

        $vendor = B2bVendor::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'agent_code' => $validated['agent_code'],
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
}
