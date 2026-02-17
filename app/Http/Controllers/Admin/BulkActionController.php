<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Tour;
use App\Models\TourCategory;
use App\Models\Package;
use App\Models\Country;
use App\Models\Province;
use App\Models\Location;
use App\Models\Hotel;
use App\Models\PackageCategory;
use App\Models\PackageInquiry;
use App\Models\User;
use App\Models\Newsletter;
use App\Models\TourReview;
use App\Models\Coupon;
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
            case 'newsletters':
                $modelClass = Newsletter::class;
                $column = 'id';
                $redirectRoute = 'admin.newsletters.index';
                break;
            case 'banners':
                $modelClass = Banner::class;
                $column = 'id';
                $redirectRoute = 'admin.banners.index';
                break;
            case 'package-categories':
                $modelClass = PackageCategory::class;
                $column = 'id';
                $redirectRoute = 'admin.package-categories.index';
                break;
            case 'packages':
                $modelClass = Package::class;
                $column = 'id';
                $redirectRoute = 'admin.packages.index';
                break;
            case 'package-inquiries':
                $modelClass = PackageInquiry::class;
                $column = 'id';
                $redirectRoute = 'admin.package-inquiries.index';
                break;
            case 'tours':
                $modelClass = Tour::class;
                $column = 'id';
                $redirectRoute = 'admin.tours.index';
                break;
            case 'tour-categories':
                $modelClass = TourCategory::class;
                $column = 'id';
                $redirectRoute = 'admin.tour-categories.index';
                break;
            case 'tour-reviews':
                $modelClass = TourReview::class;
                $column = 'id';
                $redirectRoute = 'admin.tour-reviews.index';
                break;
            case 'coupons':
                $modelClass = Coupon::class;
                $column = 'id';
                $redirectRoute = 'admin.coupons.index';
                break;
            case 'countries':
                $modelClass = Country::class;
                $column = 'id';
                $redirectRoute = 'admin.countries.index';
                break;
            case 'provinces':
                $modelClass = Province::class;
                $column = 'id';
                $redirectRoute = 'admin.provinces.index';
                break;
            case 'locations':
                $modelClass = Location::class;
                $column = 'id';
                $redirectRoute = 'admin.locations.index';
                break;
            case 'hotels':
                $modelClass = Hotel::class;
                $column = 'id';
                $redirectRoute = 'admin.hotels.index';
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
