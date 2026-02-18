<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2bVendor;

class UserController extends Controller
{
    public function index()
    {
        $users = B2bVendor::get();
        return view('admin.users-management.list', compact('users'));
    }
    
    public function changeStatus(B2bVendor $user)
    {
        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active',
        ]);
        return redirect()->route('admin.users.index')->with('notify_success', 'User status changed successfully!');
    }
    
    public function destroy(B2bVendor $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('notify_success', 'User deleted successfully!');
    }
}
