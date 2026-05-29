<?php

namespace App\Support;

use App\Models\B2bAdmin;
use Illuminate\Support\Facades\Auth;

final class B2bAdminPortalUi
{
    /**
     * @param  array{add: string, edit: string, delete: string, view?: string}  $keys
     * @return array{canAdd: bool, canView: bool, canEdit: bool, canDelete: bool, canDetail: bool, canBulk: bool, showRowActions: bool}
     */
    public static function crud(array $keys): array
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin instanceof B2bAdmin) {
            return [
                'canAdd' => false,
                'canView' => false,
                'canEdit' => false,
                'canDelete' => false,
                'canDetail' => false,
                'canBulk' => false,
                'showRowActions' => false,
            ];
        }

        $viewKey = $keys['view'] ?? self::viewKeyFromEdit($keys['edit']);
        $canAdd = $admin->hasPermission($keys['add']);
        $canView = $admin->hasPermission($viewKey);
        $canEdit = $admin->hasPermission($keys['edit']);
        $canDelete = $admin->hasPermission($keys['delete']);
        $canDetail = $canView || $canEdit;

        return [
            'canAdd' => $canAdd,
            'canView' => $canView,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
            'canDetail' => $canDetail,
            'canBulk' => $canEdit || $canDelete,
            'showRowActions' => $canEdit || $canDelete,
        ];
    }

    public static function viewKeyFromEdit(string $editKey): string
    {
        if (str_ends_with($editKey, '_edit')) {
            return substr($editKey, 0, -strlen('_edit')).'_view';
        }

        if (str_ends_with($editKey, '_manage')) {
            return substr($editKey, 0, -strlen('_manage')).'_view';
        }

        return $editKey.'_view';
    }

    public static function readOnly(string $editPermission): bool
    {
        return ! self::can($editPermission);
    }

    public static function can(string $permission): bool
    {
        $admin = Auth::guard('admin')->user();

        return $admin instanceof B2bAdmin && $admin->hasPermission($permission);
    }
}
