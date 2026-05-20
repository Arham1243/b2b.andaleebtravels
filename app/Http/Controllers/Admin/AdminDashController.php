<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2bFlightBooking;
use App\Models\B2bHotelBooking;
use App\Models\B2bInquiry;
use App\Models\B2bVendor;
use App\Models\B2bWalletRecharge;
use Illuminate\Support\Facades\Auth;

class AdminDashController extends Controller
{
    public function dashboard()
    {
        $admin = Auth::guard('admin')->user();

        $adminFirstName = 'there';
        if ($admin && filled($admin->name)) {
            $adminFirstName = explode(' ', trim((string) $admin->name), 2)[0];
        }

        $activeVendorsCount = B2bVendor::where('status', 'active')->count();
        $inactiveVendorsCount = B2bVendor::where('status', '!=', 'active')->count();
        $totalVendorsCount = B2bVendor::count();

        $hotelBookingsPending = B2bHotelBooking::where('payment_status', 'pending')->count();
        $hotelBookingsPaid = B2bHotelBooking::where('payment_status', 'paid')->count();
        $hotelBookingsFailed = B2bHotelBooking::where('payment_status', 'failed')->count();
        $hotelBookingsTotal = B2bHotelBooking::count();

        $flightBookingsPending = B2bFlightBooking::where('payment_status', 'pending')->count();
        $flightBookingsPaid = B2bFlightBooking::where('payment_status', 'paid')->count();
        $flightBookingsFailed = B2bFlightBooking::where('payment_status', 'failed')->count();
        $flightBookingsTotal = B2bFlightBooking::count();

        $walletTransfersPending = B2bWalletRecharge::query()
            ->where('payment_method', 'bank_transfer')
            ->where('status', 'pending')
            ->count();

        $inquiriesTotal = B2bInquiry::count();

        $needsAttention = $hotelBookingsPending
            + $hotelBookingsFailed
            + $flightBookingsPending
            + $flightBookingsFailed
            + $walletTransfersPending;

        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();

        $hotelPaidQuery = B2bHotelBooking::where('payment_status', 'paid');
        $flightPaidQuery = B2bFlightBooking::where('payment_status', 'paid');

        $earningsTotal = (float) (clone $hotelPaidQuery)->sum('total_amount')
            + (float) (clone $flightPaidQuery)->sum('total_amount');

        $earningsThisMonth = (float) (clone $hotelPaidQuery)->where('updated_at', '>=', $monthStart)->sum('total_amount')
            + (float) (clone $flightPaidQuery)->where('updated_at', '>=', $monthStart)->sum('total_amount');

        $earningsThisWeek = (float) (clone $hotelPaidQuery)->where('updated_at', '>=', $weekStart)->sum('total_amount')
            + (float) (clone $flightPaidQuery)->where('updated_at', '>=', $weekStart)->sum('total_amount');

        return view('admin.dashboard', [
            'title' => 'Dashboard',
            'adminFirstName' => $adminFirstName,
            'activeVendorsCount' => $activeVendorsCount,
            'inactiveVendorsCount' => $inactiveVendorsCount,
            'totalVendorsCount' => $totalVendorsCount,
            'hotelBookingsPending' => $hotelBookingsPending,
            'hotelBookingsPaid' => $hotelBookingsPaid,
            'hotelBookingsFailed' => $hotelBookingsFailed,
            'hotelBookingsTotal' => $hotelBookingsTotal,
            'flightBookingsPending' => $flightBookingsPending,
            'flightBookingsPaid' => $flightBookingsPaid,
            'flightBookingsFailed' => $flightBookingsFailed,
            'flightBookingsTotal' => $flightBookingsTotal,
            'walletTransfersPending' => $walletTransfersPending,
            'inquiriesTotal' => $inquiriesTotal,
            'needsAttention' => $needsAttention,
            'earningsTotal' => $earningsTotal,
            'earningsThisMonth' => $earningsThisMonth,
            'earningsThisWeek' => $earningsThisWeek,
        ]);
    }
}
