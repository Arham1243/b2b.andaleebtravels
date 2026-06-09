<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\B2bVendor;
use App\Support\WalletLedgerResolver;
use App\Traits\UploadImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileSettingsController extends Controller
{
    use UploadImageTrait;

    public function index()
    {
        return redirect()->route('user.profile.personalInfo');
    }

    public function personalInfo()
    {
        $user = Auth::user();

        return view('user.profile-settings.personal-info')
            ->with('title', 'Personal Information')
            ->with(compact('user'));
    }

    public function walletLedger(Request $request)
    {
        $user = Auth::user();
        $ledgerData = WalletLedgerResolver::resolve($user, $request);

        return view('user.profile-settings.wallet-ledger')
            ->with('title', 'Wallet Ledger')
            ->with([
                'user' => $user,
                'walletLedger' => $ledgerData['walletLedger'],
                'ledgerFilters' => $ledgerData['ledgerFilters'],
                'ledgerTotalCount' => $ledgerData['ledgerTotalCount'],
            ]);
    }

    public function markupSettings()
    {
        $user = B2bVendor::findOrFail(Auth::id());
        $agency = $user->walletAgency();
        if ($agency->id !== $user->id) {
            $agency = B2bVendor::findOrFail($agency->id);
        }

        return view('user.profile-settings.markup-settings')
            ->with('title', 'Markup Settings')
            ->with([
                'user' => $user,
                'agency' => $agency,
            ]);
    }

    public function updateMarkupSettings(Request $request)
    {
        $validated = $request->validate([
            'agent_markup_override_enabled' => 'nullable|boolean',
            'agent_flight_markup_type' => 'nullable|in:percent,fixed',
            'agent_flight_markup_value' => 'nullable|numeric|min:0|max:99999999.99',
            'agent_hotel_markup_type' => 'nullable|in:percent,fixed',
            'agent_hotel_markup_value' => 'nullable|numeric|min:0|max:99999999.99',
        ]);

        $overrideEnabled = $request->boolean('agent_markup_override_enabled');
        $flightType = $overrideEnabled
            ? $this->normalizeAgentMarkupType($validated['agent_flight_markup_type'] ?? null, (float) ($validated['agent_flight_markup_value'] ?? 0))
            : null;
        $hotelType = $overrideEnabled
            ? $this->normalizeAgentMarkupType($validated['agent_hotel_markup_type'] ?? null, (float) ($validated['agent_hotel_markup_value'] ?? 0))
            : null;

        B2bVendor::where('id', Auth::id())->update([
            'agent_markup_override_enabled' => $overrideEnabled,
            'agent_flight_markup_type' => $flightType,
            'agent_flight_markup_value' => $overrideEnabled
                ? $this->normalizeAgentMarkupValue($validated['agent_flight_markup_type'] ?? null, $validated['agent_flight_markup_value'] ?? 0)
                : 0,
            'agent_hotel_markup_type' => $hotelType,
            'agent_hotel_markup_value' => $overrideEnabled
                ? $this->normalizeAgentMarkupValue($validated['agent_hotel_markup_type'] ?? null, $validated['agent_hotel_markup_value'] ?? 0)
                : 0,
        ]);

        return redirect()->back()->with('notify_success', 'Markup settings updated successfully.');
    }

    public function updatePersonalInfo(Request $request)
    {
        $vendorId = Auth::user()->id;

        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'agency_logo' => 'nullable|image|max:2048',
        ]);

        // Travel agency name, username, and trade license fields are admin-managed only.
        $data = $validatedData;

        $vendor = B2bVendor::findOrFail($vendorId);

        if ($request->hasFile('agency_logo')) {
            $data['agency_logo'] = $this->uploadImage(
                $request->file('agency_logo'),
                'Vendors/AgencyLogo',
                $vendor->agency_logo
            );
        }

        B2bVendor::where('id', Auth::user()->id)->update($data);

        return redirect()->back()->with('notify_success', 'Information Updated Successfully');
    }

    public function changePassword()
    {
        $user = Auth::user();

        return view('user.profile-settings.change-password')->with('title', 'Change Password')->with(compact('user'));
    }

    public function updatePassword(Request $request)
    {
        $validatedData = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Check if user exists and is Google-authenticated
        $existingUser = Auth::user();

        if ($existingUser && $existingUser->auth_provider === 'google') {
            return back()
                ->withInput()
                ->with('notify_error', 'This account uses Google sign-in. Password changes are managed by Google.');
        }

        B2bVendor::where('id', Auth::user()->id)->update([
            'password' => bcrypt($validatedData['password']),
        ]);

        return redirect()->back()->with('notify_success', 'Password updated successfully');
    }

    private function normalizeAgentMarkupType(?string $type, float $value): ?string
    {
        $type = strtolower(trim((string) $type));

        if (! in_array($type, ['percent', 'fixed'], true) || $value <= 0) {
            return null;
        }

        return $type;
    }

    private function normalizeAgentMarkupValue(?string $type, mixed $value): float
    {
        $type = strtolower(trim((string) $type));
        $numeric = round((float) $value, 2);

        if (! in_array($type, ['percent', 'fixed'], true) || $numeric <= 0) {
            return 0;
        }

        return $numeric;
    }
}
