<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2bAdmin;
use App\Models\B2bAdminRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class PortalAdminController extends Controller
{
    public function index()
    {
        $admins = B2bAdmin::with('adminRole')->orderBy('id')->get();
        $title = 'Portal administrators';

        return view('admin.portal-admins.index', compact('admins', 'title'));
    }

    public function create()
    {
        $title = 'Add portal administrator';
        $roles = $this->assignableRoles();

        return view('admin.portal-admins.create', compact('title', 'roles'));
    }

    public function store(Request $request)
    {
        $roleIds = $this->assignableRoles()->pluck('id')->all();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:b2b_admins,email',
            'admin_role_id' => ['required', Rule::in($roleIds)],
        ]);

        $role = B2bAdminRole::findOrFail($data['admin_role_id']);
        if ($role->is_super && ! auth()->guard('admin')->user()->isSuperAdmin()) {
            abort(403, 'Only super administrators can assign the super administrator role.');
        }

        $admin = B2bAdmin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => null,
            'admin_role_id' => $role->id,
            'role' => $role->is_super ? 'admin' : 'staff',
            'status' => B2bAdmin::STATUS_ACTIVE,
        ]);

        Password::broker('admins')->sendResetLink(['email' => $admin->email]);

        return redirect()
            ->route('admin.portal-users.index')
            ->with('notify_success', 'Administrator created. They have been emailed a link to set their password.');
    }

    public function edit(B2bAdmin $portal_user)
    {
        $this->authorizeSuperOnlyTarget($portal_user);

        $title = 'Edit portal administrator';
        $adminUser = $portal_user;
        $roles = $this->assignableRoles();

        return view('admin.portal-admins.edit', compact('adminUser', 'title', 'roles'));
    }

    public function update(Request $request, B2bAdmin $portal_user)
    {
        $this->authorizeSuperOnlyTarget($portal_user);

        $roleIds = $this->assignableRoles()->pluck('id')->all();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:b2b_admins,email,'.$portal_user->id,
            'admin_role_id' => ['required', Rule::in($roleIds)],
            'status' => ['required', Rule::in([B2bAdmin::STATUS_ACTIVE, B2bAdmin::STATUS_INACTIVE])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ((int) $portal_user->id === (int) auth()->guard('admin')->id()
            && $data['status'] === B2bAdmin::STATUS_INACTIVE) {
            return back()->withInput()->with('notify_error', 'You cannot deactivate your own account.');
        }

        $role = B2bAdminRole::findOrFail($data['admin_role_id']);
        if ($role->is_super && ! auth()->guard('admin')->user()->isSuperAdmin()) {
            abort(403, 'Only super administrators can assign the super administrator role.');
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'admin_role_id' => $role->id,
            'role' => $role->is_super ? 'admin' : 'staff',
            'status' => $data['status'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $portal_user->update($payload);

        return redirect()
            ->route('admin.portal-users.index')
            ->with('notify_success', 'Administrator updated.');
    }

    public function destroy(B2bAdmin $portal_user)
    {
        $this->authorizeSuperOnlyTarget($portal_user);

        if ((int) $portal_user->id === (int) auth()->guard('admin')->id()) {
            return back()->with('notify_error', 'You cannot delete your own account.');
        }

        $portal_user->delete();

        return redirect()
            ->route('admin.portal-users.index')
            ->with('notify_success', 'Administrator removed.');
    }

    protected function authorizeSuperOnlyTarget(?B2bAdmin $target): void
    {
        if ($target !== null && $target->isSuperAdmin() && ! auth()->guard('admin')->user()->isSuperAdmin()) {
            abort(403, 'Only super administrators can manage this account.');
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, B2bAdminRole>
     */
    protected function assignableRoles()
    {
        $query = B2bAdminRole::query()->orderByDesc('is_super')->orderBy('name');

        if (! auth()->guard('admin')->user()->isSuperAdmin()) {
            $query->where('is_super', false);
        }

        return $query->get();
    }
}
