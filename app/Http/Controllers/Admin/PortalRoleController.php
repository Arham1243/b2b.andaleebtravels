<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2bAdminRole;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PortalRoleController extends Controller
{
    public function index()
    {
        $roles = B2bAdminRole::withCount('admins')->orderByDesc('is_super')->orderBy('name')->get();
        $title = 'Portal roles';

        return view('admin.portal-roles.index', compact('roles', 'title'));
    }

    public function create()
    {
        $title = 'Add portal role';
        $permissionUi = config('b2b_admin_permissions');

        return view('admin.portal-roles.create', compact('title', 'permissionUi'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(self::allowedPermissionKeys())],
        ]);

        $slugBase = Str::slug($data['name']) ?: 'role';
        $slug = $slugBase;
        $i = 1;
        while (B2bAdminRole::where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.(++$i);
        }

        B2bAdminRole::create([
            'name' => $data['name'],
            'slug' => $slug,
            'is_super' => false,
            'permissions' => array_values(array_unique($data['permissions'] ?? [])),
        ]);

        return redirect()->route('admin.portal-roles.index')->with('notify_success', 'Role created.');
    }

    public function edit(B2bAdminRole $portal_role)
    {
        abort_if($portal_role->is_super, 403, 'The super administrator role cannot be edited here.');

        $title = 'Edit portal role';
        $role = $portal_role;
        $permissionUi = config('b2b_admin_permissions');

        return view('admin.portal-roles.edit', compact('role', 'title', 'permissionUi'));
    }

    public function update(Request $request, B2bAdminRole $portal_role)
    {
        abort_if($portal_role->is_super, 403, 'The super administrator role cannot be edited here.');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(self::allowedPermissionKeys())],
        ]);

        $portal_role->update([
            'name' => $data['name'],
            'permissions' => array_values(array_unique($data['permissions'] ?? [])),
        ]);

        return redirect()->route('admin.portal-roles.index')->with('notify_success', 'Role updated.');
    }

    public function destroy(B2bAdminRole $portal_role)
    {
        abort_if($portal_role->is_super, 403, 'The super administrator role cannot be deleted.');

        if ($portal_role->admins()->exists()) {
            return back()->with('notify_error', 'Reassign or remove administrators using this role first.');
        }

        $portal_role->delete();

        return redirect()->route('admin.portal-roles.index')->with('notify_success', 'Role deleted.');
    }

    /**
     * @param  list<string>  $stored
     * @return list<string>
     */
    public static function permissionsSelectedForDisplay(array $stored): array
    {
        return array_values(array_unique(array_filter($stored, fn ($key) => is_string($key) && $key !== '')));
    }

    /** @return list<string> */
    protected static function allowedPermissionKeys(): array
    {
        return self::flattenPermissionKeys(config('b2b_admin_permissions.groups', []));
    }

    /**
     * @param  list<array{items?: array<string, mixed>}>  $groups
     * @return list<string>
     */
    protected static function flattenPermissionKeys(array $groups): array
    {
        $keys = [];

        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }
            foreach (($group['items'] ?? []) as $permKey => $_spec) {
                if (is_string($permKey) && preg_match('/^[a-z][a-z0-9_]*$/', $permKey)) {
                    $keys[] = $permKey;
                }
            }
        }

        return array_values(array_unique($keys));
    }
}
