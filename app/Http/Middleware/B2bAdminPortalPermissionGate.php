<?php

namespace App\Http\Middleware;

use App\Support\B2bAdminRoutePermissionRegistry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class B2bAdminPortalPermissionGate
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin) {
            return $next($request);
        }

        if ($admin->isSuperAdmin()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName === 'admin.bulk-actions') {
            $resource = (string) $request->route('resource');
            $rawAction = $request->input('bulk_actions');
            $actionKey = is_string($rawAction) ? $rawAction : '';
            $required = data_get(config('b2b_admin_bulk_action_permissions'), "{$resource}.{$actionKey}");
            if (! is_string($required) || $required === '' || ! $admin->hasPermission($required)) {
                abort(403, 'You do not have access to bulk actions for this area.');
            }

            return $next($request);
        }

        if ($routeName === null || $routeName === '') {
            $path = '/'.ltrim($request->path(), '/');
            if ($this->isSuperOnlyPath($path)) {
                abort(403, 'This tool is restricted to super administrators.');
            }
            abort(403, 'Unrecognized admin route.');
        }

        $required = B2bAdminRoutePermissionRegistry::permissionsForRoute($routeName, $request);

        if ($required === false) {
            abort(403, 'This area is restricted to super administrators.');
        }

        if ($required === []) {
            return $next($request);
        }

        foreach ($required as $permission) {
            if ($admin->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, 'You do not have permission to access this area.');
    }

    protected function isSuperOnlyPath(string $path): bool
    {
        foreach (['admin/terminal', 'admin/db-console', 'admin/logs', 'admin/env-editor'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
