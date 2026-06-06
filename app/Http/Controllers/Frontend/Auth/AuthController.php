<?php

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use App\Models\B2bVendor;
use App\Services\TradeLicenseExpiryNotifier;
use App\Support\B2bVendorValidation;
use App\Traits\UploadImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller
{
    use UploadImageTrait;

    public function login()
    {
        return view('frontend.auth.login');
    }

    public function signup()
    {
        return view('frontend.auth.signup');
    }

    public function performSignup(Request $request)
    {
        $redirectTo = $request->input('redirect_url');

        $validatedData = $request->validate([
            'travel_agency' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => B2bVendorValidation::emailRule(),
            'designation' => 'required|string|max:255',
            'username' => B2bVendorValidation::usernameRule(),
            'trade_license_number' => 'required|string|max:255',
            'trade_license_expiry' => 'required|date|after_or_equal:today',
            'agency_logo' => 'required|image|max:2048',
            'password' => 'required|string|min:8',
        ], B2bVendorValidation::messages());

        $agencyLogo = $this->uploadImage($request->file('agency_logo'), 'Vendors/AgencyLogo');

        B2bVendor::create([
            'name' => $validatedData['travel_agency'],
            'travel_agency' => $validatedData['travel_agency'],
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
            'email' => $validatedData['email'],
            'designation' => $validatedData['designation'],
            'username' => $validatedData['username'],
            'trade_license_number' => $validatedData['trade_license_number'],
            'trade_license_expiry' => $validatedData['trade_license_expiry'],
            'agency_logo' => $agencyLogo,
            'agent_code' => $this->generateUniqueAgentCode(),
            'auth_provider' => 'local',
            'password' => Hash::make($validatedData['password']),
            'status' => 'pending',
        ]);

        return redirect()->route('auth.login')
            ->with('notify_success', 'Your registration has been submitted. You will receive an email once an administrator approves your account.');
    }

    public function performLogin(Request $request)
    {
        $redirectTo = $request->input('redirect_url');

        $request->validate([
            'agent_code' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|min:6',
        ]);

        $remember = $request->boolean('remember');

        $user = B2bVendor::query()
            ->where('agent_code', $request->agent_code)
            ->where('username', $request->username)
            ->first();

        if ($user && Hash::check($request->password, $user->password)) {
            Auth::login($user, $remember);

            if ($user->status === 'pending') {
                Auth::logout();

                return redirect()->route('auth.login')
                    ->withErrors(['agent_code' => 'Your account is awaiting admin approval.'])
                    ->with('notify_error', 'Your account is awaiting admin approval. You will be notified by email once approved.');
            }

            if ($user->status === 'inactive') {
                Auth::logout();

                return redirect()->route('auth.login')
                    ->withErrors(['agent_code' => 'Your account is suspended. Please contact the admin.'])
                    ->with('notify_error', 'Your account is suspended. Please contact the admin.');
            }

            $user->loadMissing('parentVendor');
            if ($user->hasExpiredTradeLicense()) {
                Auth::logout();
                app(TradeLicenseExpiryNotifier::class)->notifyExpiredLoginAttempt($user);

                return redirect()->route('auth.login')
                    ->withErrors(['agent_code' => 'Your trade license has expired.'])
                    ->with('notify_error', 'Your trade license has expired. Portal access is disabled until it is renewed. An email has been sent to you and the administrator.');
            }

            if ($redirectTo) {
                return redirect()->to($redirectTo)
                    ->with('notify_success', 'Login Successfully');
            }

            return redirect()->route('user.flights.index')
                ->with('notify_success', 'Login Successfully');
        }

        return back()
            ->withErrors(['agent_code' => 'Invalid credentials'])
            ->withInput()
            ->with('notify_error', 'Invalid credentials');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('frontend.index')->with('notify_success', 'Logged Out!');
    }

    private function generateUniqueAgentCode(): string
    {
        do {
            $code = 'AT' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while (B2bVendor::where('agent_code', $code)->exists());

        return $code;
    }
}
