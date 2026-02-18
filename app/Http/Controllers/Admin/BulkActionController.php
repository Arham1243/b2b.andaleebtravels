<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class BulkActionController extends Controller
{
    public function handle(Request $request, $resource)
    {
        $action = $request->input('bulk_actions');
        $selectedIds = $request->input('bulk_select', []);
        if (empty($selectedIds)) {
            return Redirect::back()->with('notify_error', 'No items selected for the bulk action.');
        }

        $isParent = false;
        switch ($resource) {
            case 'users':
                $modelClass = User::class;
                $column = 'id';
                $redirectRoute = 'admin.users.index';
                break;
            case 'inquiries':
                $modelClass = Inquiry::class;
                $column = 'id';
                $redirectRoute = 'admin.inquiries.index';
                break;
            default:
                return Redirect::back()->with('notify_error', 'Resource not found.');
        }

        return $this->handleBulkActions($modelClass, $column, $action, $selectedIds, $redirectRoute, $isParent);
    }

    protected function handleBulkActions($modelClass, $idColumn, $action, $selectedIds, $redirectRoute, $isParent = false)
    {
        switch ($action) {
            case 'delete':
                $modelClass::whereIn($idColumn, $selectedIds)->each(function ($model) use ($modelClass, $isParent) {
                    $model->delete();
                });
                break;
            case 'active':
                $modelClass::whereIn($idColumn, $selectedIds)->update(['status' => 'active']);
                break;
            case 'inactive':
                $modelClass::whereIn($idColumn, $selectedIds)->update(['status' => 'inactive']);
                break;

            default:
                break;
        }

        return redirect()->route($redirectRoute)->with('notify_success', 'Bulk action performed successfully!');
    }
}
