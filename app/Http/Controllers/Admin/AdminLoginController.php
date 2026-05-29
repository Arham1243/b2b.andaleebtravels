<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2bAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminLoginController extends Controller
{
    public function login()
    {
        $adminGuard = Auth::guard('admin');

        if ($adminGuard->check()) {
            return redirect()->route('admin.dashboard')->with('notify_success', 'You are already logged in as Admin');
        }

        return view('admin.login', ['title' => 'Admin Login']);
    }

    public function performLogin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        $admin = B2bAdmin::where('email', $validated['email'])->first();

        if ($admin === null || ! $admin->isPortalActive()) {
            return redirect()
                ->back()
                ->withErrors(['email' => 'Invalid Credentials'])
                ->withInput($request->except('password'))
                ->with('notify_error', 'Invalid Credentials');
        }

        if ($admin->password === null || $admin->password === '' || ! Hash::check($validated['password'], $admin->password)) {
            return redirect()
                ->back()
                ->withErrors(['email' => 'Invalid Credentials'])
                ->withInput($request->except('password'))
                ->with('notify_error', 'Invalid Credentials');
        }

        Auth::guard('admin')->login($admin, $request->boolean('remember'));

        return redirect()->intended('admin/dashboard')->with('notify_success', 'You are logged in as Admin');
    }

    public function logout()
    {
        Auth::guard('admin')->logout();

        return redirect()->route('admin.login')->with('notify_success', 'Logged Out!');
    }
}
