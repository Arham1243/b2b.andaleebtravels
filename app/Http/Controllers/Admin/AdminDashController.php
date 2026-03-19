<?php

namespace App\Http\Controllers\Admin;

use App\Models\B2bVendor;
use App\Http\Controllers\Controller;

class AdminDashController extends Controller
{
    public function dashboard()
    {
        $vendors = B2bVendor::where('status', 'active')->get();
        return view('admin.dashboard', ['title' => 'Dashboard', 'vendors' => $vendors]);
    }
}
