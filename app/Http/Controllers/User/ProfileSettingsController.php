<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\B2bVendor;
use App\Models\Config;
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
        $config = Config::pluck('config_value', 'config_key')->toArray();
        $adminProviders = $this->parseProviderConfig($config['HOTEL_SEARCH_PROVIDERS'] ?? null, ['yalago', 'tbo', 'tripindeal']);
        $adminFlightProviders = $this->parseProviderConfig($config['FLIGHT_SEARCH_PROVIDERS'] ?? null, ['sabre']) ?? ['sabre'];

        return view('user.profile-settings.personal-info')
            ->with('title', 'Personal Information')
            ->with(compact('user', 'adminProviders', 'adminFlightProviders'));
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

    public function updatePersonalInfo(Request $request)
    {
        $vendorId = Auth::user()->id;

        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'agency_logo' => 'nullable|image|max:2048',
            'hotel_search_providers' => 'nullable|array',
            'hotel_search_providers.*' => 'in:yalago,tbo,tripindeal',
            'flight_search_providers' => 'nullable|array',
            'flight_search_providers.*' => 'in:sabre',
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

        $data['hotel_search_providers'] = $this->parseProviderConfig($request->input('hotel_search_providers'), ['yalago', 'tbo', 'tripindeal']);
        $data['flight_search_providers'] = $this->parseProviderConfig($request->input('flight_search_providers'), ['sabre']);

        B2bVendor::where('id', Auth::user()->id)->update($data);

        return redirect()->back()->with('notify_success', 'Information Updated Successfully');
    }

    private function parseProviderConfig($raw, array $allowed): ?array
    {
        if (empty($raw)) {
            return null;
        }

        $providers = [];

        if (is_array($raw)) {
            $providers = $raw;
        } elseif (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $providers = $decoded;
            } else {
                $providers = array_map('trim', explode(',', $raw));
            }
        }

        $providers = array_values(array_unique(array_filter(array_map(function ($value) {
            return strtolower(trim((string) $value));
        }, $providers))));

        $providers = array_values(array_intersect($providers, $allowed));

        return empty($providers) ? null : $providers;
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
}
