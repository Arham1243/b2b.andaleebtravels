<?php

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

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
    public function myBooking()
    {
        return view('frontend.auth.my-booking');
    }

    public function performSignup(Request $request)
    {
        $redirectTo = $request->input('redirect_url');

        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        $existingUser = User::where('email', $validatedData['email'])->first();

        if ($existingUser) {
            if ($existingUser->auth_provider === 'google') {
                return redirect()->back()->with('notify_error', 'This email is registered via Google. Please continue with Google.');
            } else {
                return redirect()->back()->with('notify_error', 'The email has already been taken.');
            }
        }

        $user = User::create([
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
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
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        // Check if user exists and is Google-authenticated
        $existingUser = User::where('email', $request->email)->first();

        if ($existingUser && $existingUser->auth_provider === 'google') {
            return back()
                ->withInput()
                ->with('notify_error', 'This email is registered via Google. Please continue with Google.');
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt(
            ['email' => $request->email, 'password' => $request->password],
            $remember
        )) {
            $user = Auth::user();

            if ($user->status === 'inactive') {
                Auth::logout();

                return redirect()->route('auth.login')
                    ->withErrors(['email' => 'Your account is suspended. Please contact the admin.'])
                    ->with('notify_error', 'Your account is suspended. Please contact the admin.');
            }

            if ($redirectTo) {
                return redirect()->to($redirectTo)
                    ->with('notify_success', 'Login Successfully');
            }

            return redirect()->route('frontend.index')
                ->with('notify_success', 'Login Successfully');
        }

        return back()
            ->withErrors(['email' => 'Invalid credentials'])
            ->withInput()
            ->with('notify_error', 'Invalid credentials');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('frontend.index')->with('notify_success', 'Logged Out!');
    }

    public function sendBookingByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'order_number' => 'nullable|string|exists:orders,order_number',
        ]);

        $query = Order::with('orderItems')
            ->where('passenger_email', $request->email);

        if ($request->filled('order_number')) {
            $query->where('order_number', $request->order_number);
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            return back()->with('notify_error', 'No orders found for this email/order number.');
        }

        Mail::send('emails.user-bookings', ['orders' => $orders], function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Your Booking Details');
        });

        return back()->with('notify_success', 'Booking details sent to your email.');
    }
}
