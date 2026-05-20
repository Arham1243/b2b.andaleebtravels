<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next)
    {

        if (Auth::check()) {
            $user = Auth::user();

            if ($user->status === 'pending') {
                Auth::logout();

                return redirect()->route('auth.login')
                    ->withErrors(['agent_code' => 'Your account is awaiting admin approval.'])
                    ->with('notify_error', 'Your account is awaiting admin approval.');
            }

            if ($user->status === 'inactive') {
                Auth::logout();

                return redirect()->route('auth.login')
                    ->withErrors(['email' => 'Your account is suspended. Please contact the admin.'])
                    ->with('notify_error', 'Your account is suspended. Please contact the admin.');
            }

            $user->loadMissing('parentVendor');
            if ($user->hasExpiredTradeLicense()) {
                Auth::logout();

                return redirect()->route('auth.login')
                    ->withErrors(['agent_code' => 'Your trade license has expired.'])
                    ->with('notify_error', 'Your trade license has expired. Please contact the administrator.');
            }
        }

        return $next($request);
    }
}
