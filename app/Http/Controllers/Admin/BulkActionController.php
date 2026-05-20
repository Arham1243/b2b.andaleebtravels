<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2bInquiry;
use App\Models\B2bVendor;
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
            case 'vendors':
                if ($action === 'delete') {
                    return $this->bulkDeleteVendors($selectedIds);
                }

                $modelClass = B2bVendor::class;
                $column = 'id';
                $redirectRoute = 'admin.vendors.index';
                break;
            case 'inquiries':
                $modelClass = B2bInquiry::class;
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

    protected function bulkDeleteVendors(array $selectedIds)
    {
        $blocked = [];
        $deleted = 0;

        foreach ($selectedIds as $id) {
            $vendor = B2bVendor::find($id);
            if (!$vendor) {
                continue;
            }

            if ($vendor->hasAssociatedData()) {
                $blocked[] = $vendor->name;
                continue;
            }

            $vendor->delete();
            $deleted++;
        }

        if (!empty($blocked)) {
            $message = 'Cannot delete vendor(s) with existing bookings or related data: ' . implode(', ', $blocked);
            if ($deleted > 0) {
                $message .= '. ' . $deleted . ' vendor(s) without data were deleted.';
            }

            return redirect()->route('admin.vendors.index')->with('notify_error', $message);
        }

        if ($deleted === 0) {
            return redirect()->route('admin.vendors.index')->with('notify_error', 'No vendors were deleted.');
        }

        return redirect()->route('admin.vendors.index')->with('notify_success', 'Selected vendor(s) deleted successfully!');
    }
}
