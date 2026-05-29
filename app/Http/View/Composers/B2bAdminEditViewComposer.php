<?php

namespace App\Http\View\Composers;

use App\Support\B2bAdminPortalUi;
use App\Support\B2bAdminRoutePermissionRegistry;
use Illuminate\View\View;

class B2bAdminEditViewComposer
{
    public function compose(View $view): void
    {
        $name = $view->getName();
        if ($name === null || ! str_ends_with($name, '.edit')) {
            return;
        }

        $routeName = request()->route()?->getName();
        if ($routeName === null) {
            return;
        }

        $required = B2bAdminRoutePermissionRegistry::permissionsForRoute($routeName, request());
        if (! is_array($required)) {
            return;
        }

        $editPermission = null;
        foreach ($required as $permission) {
            if (is_string($permission) && (str_ends_with($permission, '_edit') || str_ends_with($permission, '_manage'))) {
                $editPermission = $permission;
                break;
            }
        }

        if ($editPermission === null || $view->offsetExists('readOnly')) {
            return;
        }

        $view->with('readOnly', B2bAdminPortalUi::readOnly($editPermission));
    }
}
