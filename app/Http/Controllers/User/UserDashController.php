<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;

class UserDashController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        // Get orders for this user (both authenticated and guest orders by email)
        $orders = Order::with('orderItems.tour')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('passenger_email', $user->email);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('user.dashboard')->with('title', 'Dashboard')->with(compact('orders'));
    }
}
