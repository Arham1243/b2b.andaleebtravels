<?php

use App\Models\B2bVendor;
use App\Models\B2bWalletRecharge;

$vendorPendingCount = B2bVendor::pendingSignups()->count();
$walletBankTransfersPendingCount = B2bWalletRecharge::query()
    ->where('payment_method', 'bank_transfer')
    ->where('status', 'pending')
    ->count();

return [
    [
        'title' => 'Dashboard',
        'icon' => 'bx bxs-home',
        'route' => route('admin.dashboard'),
    ],
    [
        'title' => 'Vendors',
        'icon' => 'bx bxs-briefcase',
        'submenu' => [
            [
                'title' => 'Manage Vendors',
                'icon' => 'bx bxs-user-detail',
                'route' => route('admin.vendors.index'),
                'permission' => 'vendors_view',
            ],
            [
                'title' => 'Signup Requests',
                'icon' => 'bx bxs-time-five',
                'route' => route('admin.vendors.pending.index'),
                'permission' => 'vendors_pending_view',
                'badge_count' => $vendorPendingCount,
            ],
        ],
    ],
    [
        'title' => 'Hotel Bookings',
        'icon' => 'bx bxs-hotel',
        'route' => route('admin.hotel-bookings.index'),
        'permission' => 'hotel_bookings_view',
    ],
    [
        'title' => 'Flight Bookings',
        'icon' => 'bx bxs-plane',
        'route' => route('admin.flight-bookings.index'),
        'permission' => 'flight_bookings_view',
    ],
    [
        'title' => 'Inquiries',
        'icon' => 'bx bxs-message-dots',
        'route' => route('admin.inquiries.index'),
        'permission' => 'inquiries_view',
    ],
    [
        'title' => 'Wallet Bank Transfers',
        'icon' => 'bx bxs-bank',
        'route' => route('admin.wallet.bank-transfers.index'),
        'permission' => 'wallet_transfers_view',
        'badge_count' => $walletBankTransfersPendingCount,
    ],
    [
        'title' => 'Portal admins',
        'icon' => 'bx bxs-lock-alt',
        'route' => route('admin.portal-users.index'),
        'permission' => 'portal_admins_view',
    ],
    [
        'title' => 'Portal roles',
        'icon' => 'bx bxs-shield',
        'route' => route('admin.portal-roles.index'),
        'permission' => 'portal_roles_view',
    ],
    [
        'title' => 'Layout',
        'icon' => 'bx bxs-cog',
        'submenu' => [
            [
                'title' => 'Logo Management',
                'icon' => 'bx bx-image',
                'route' => route('admin.settings.logo'),
                'permission' => 'settings_view',
            ],
            [
                'title' => 'Configuration',
                'icon' => 'bx bxs-contact',
                'route' => route('admin.settings.details'),
                'permission' => 'settings_view',
            ],
        ],
    ],
];
