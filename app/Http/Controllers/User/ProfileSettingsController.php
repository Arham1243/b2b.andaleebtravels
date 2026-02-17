<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\UploadImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileSettingsController extends Controller
{
    use UploadImageTrait;

    public function index()
    {
        $user = Auth::user();

        return view('user.profile-settings.personal-info')->with('title', 'Personal Information')->with(compact('user'));
    }

    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'country' => 'nullable|string',
            'city' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('avatar')) {
            $avatar = asset($this->simpleUploadImg($request->file('avatar'), 'Users/Avatar'));
        }

        $data = array_merge($validatedData, [
            'avatar' => $avatar,
        ]);

        User::where('id', Auth::user()->id)->update($data);

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

        Auth::user()->update([
            'password' => bcrypt($validatedData['password']),
        ]);

        return redirect()->back()->with('notify_success', 'Password updated successfully');
    }
}
