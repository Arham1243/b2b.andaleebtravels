<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::get();
        return view('admin.users-management.list', compact('users'));
    }
    
    public function changeStatus(User $user)
    {
        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active',
        ]);
        return redirect()->route('admin.users.index')->with('notify_success', 'User status changed successfully!');
    }
    
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('notify_success', 'User deleted successfully!');
    }
}
