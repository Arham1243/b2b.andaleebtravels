<?php

namespace App\Http\Middleware;

use App\Models\B2bAdmin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            $request->session()->put('url.intended', $request->url());

            return redirect()->route('admin.login')->with('notify_error', 'You need to login before accessing Admin Dashboard');
        }

        /** @var B2bAdmin $admin */
        $admin = Auth::guard('admin')->user();
        $admin->loadMissing('adminRole');

        if (! $admin->isPortalActive()) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->with('notify_error', 'Your portal account is inactive.');
        }

        View::share('portalIsStaff', $admin->isStaff());
        View::share('portalIsFullAdmin', $admin->isFullAdmin());

        $menuItems = self::filterAdminMenu(require resource_path('views/admin/layouts/menu-config.php'), $admin);
        View::share('adminMenuItems', $menuItems);

        return $next($request);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    protected static function filterAdminMenu(array $items, B2bAdmin $admin): array
    {
        $out = [];
        foreach ($items as $item) {
            if (! empty($item['submenu'])) {
                $sub = self::filterAdminMenu($item['submenu'], $admin);
                if ($sub === []) {
                    continue;
                }
                $item['submenu'] = array_values($sub);
                $out[] = $item;

                continue;
            }

            $perm = $item['permission'] ?? null;
            if ($perm && ! $admin->hasPermission($perm)) {
                continue;
            }
            $out[] = $item;
        }

        return $out;
    }
}
