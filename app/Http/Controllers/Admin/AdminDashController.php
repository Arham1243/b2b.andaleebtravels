<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Tour;
use App\Http\Controllers\Controller;

class AdminDashController extends Controller
{
    public function dashboard()
    {
        $users = User::where('status', 'active')->get();
        return view('admin.dashboard', ['title' => 'Dashboard', 'users' => $users]);
    }
}
