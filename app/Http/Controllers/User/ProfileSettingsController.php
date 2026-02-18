<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\B2bVendor;
use App\Traits\UploadImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileSettingsController extends Controller
{
    use UploadImageTrait;

    public function personalInfo()
    {
        $user = Auth::user();

        return view('user.profile-settings.personal-info')->with('title', 'Personal Information')->with(compact('user'));
    }

    public function updatePersonalInfo(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'agent_code' => 'required|string|max:255',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $avatar = $this->uploadImage($request->file('avatar'), 'Users/Avatar');
        }

        $data = array_merge($validatedData, [
            'avatar' => $avatar,
        ]);

        B2bVendor::where('id', Auth::user()->id)->update($data);

        return redirect()->back()->with('notify_success', 'Information Updated Successfully');
    }

    public function changePassword()
    {
        $user = Auth::user();

        return view('user.profile-settings.change-password')->with('title', 'Change Password')->with(compact('user'));
    }

    public function updatePassword(Request $request)
    {
        $validatedData = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Check if user exists and is Google-authenticated
        $existingUser = Auth::user();

        if ($existingUser && $existingUser->auth_provider === 'google') {
            return back()
                ->withInput()
                ->with('notify_error', 'This account uses Google sign-in. Password changes are managed by Google.');
        }

        B2bVendor::where('id', Auth::user()->id)->update([
            'password' => bcrypt($validatedData['password']),
        ]);

        return redirect()->back()->with('notify_success', 'Password updated successfully');
    }
}
