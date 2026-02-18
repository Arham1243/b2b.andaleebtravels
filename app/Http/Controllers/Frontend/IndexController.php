<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Inquiry;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class IndexController extends Controller
{
    public function index()
    {
        return view('frontend.home');
    }

    public function submitContact(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'message' => 'required|string',
                'g-recaptcha-response' => 'required',
            ]);

            Inquiry::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'message' => $request->message,
            ]);

            return back()->with('notify_success', 'Thank you for contacting us! We will get back to you soon.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = collect($e->errors())->flatten()->join(', ');
            return back()->with('notify_error', $errors);
        }
    }
}
