<?php

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use App\Models\B2bVendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
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
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'agent_code' => 'required',
            'email' => 'required|email|unique:b2b_vendors,email|max:255',
            'password' => 'required|string|min:8',
        ]);

        $user = B2bVendor::create([
            'name' => $validatedData['name'],
            'username' => $validatedData['username'],
            'agent_code' => $validatedData['agent_code'],
            'email' => $validatedData['email'],
            'auth_provider' => 'local',
            'password' => Hash::make($validatedData['password']),
        ]);

        Auth::login($user);

        if ($redirectTo) {
            return redirect()->to($redirectTo)->with('notify_success', 'Account Created Successfully');
        }

        return redirect()->route('frontend.index')->with('notify_success', 'Account Created Successfully');
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

        if (Auth::attempt(
            ['agent_code' => $request->agent_code, 'password' => $request->password],
            $remember
        )) {
            $user = Auth::user();

            if ($user->status === 'inactive') {
                Auth::logout();

                return redirect()->route('auth.login')
                    ->withErrors(['agent_code' => 'Your account is suspended. Please contact the admin.'])
                    ->with('notify_error', 'Your account is suspended. Please contact the admin.');
            }

            if ($redirectTo) {
                return redirect()->to($redirectTo)
                    ->with('notify_success', 'Login Successfully');
            }

            return redirect()->route('user.dashboard')
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
}
