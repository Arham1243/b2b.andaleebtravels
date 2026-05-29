<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureB2bSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin || ! $admin->isSuperAdmin()) {
            abort(403, 'Only super administrators can perform this action.');
        }

        return $next($request);
    }
}
