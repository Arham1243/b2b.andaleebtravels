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
            ],
            [
                'title' => 'Signup Requests',
                'icon' => 'bx bxs-time-five',
                'route' => route('admin.vendors.pending.index'),
                'badge_count' => $vendorPendingCount,
            ],
        ],
    ],
    [
        'title' => 'Hotel Bookings',
        'icon' => 'bx bxs-hotel',
        'route' => route('admin.hotel-bookings.index'),
    ],
    [
        'title' => 'Flight Bookings',
        'icon' => 'bx bxs-plane',
        'route' => route('admin.flight-bookings.index'),
    ],
    [
        'title' => 'Inquiries',
        'icon' => 'bx bxs-message-dots',
        'route' => route('admin.inquiries.index'),
    ],
    [
        'title' => 'Wallet Bank Transfers',
        'icon' => 'bx bxs-bank',
        'route' => route('admin.wallet.bank-transfers.index'),
        'badge_count' => $walletBankTransfersPendingCount,
    ],
    [
        'title' => 'Layout',
        'icon' => 'bx bxs-cog',
        'submenu' => [
            [
                'title' => 'Logo Management',
                'icon' => 'bx bx-image',
                'route' => route('admin.settings.logo'),
            ],
            [
                'title' => 'Configuration',
                'icon' => 'bx bxs-contact',
                'route' => route('admin.settings.details'),
            ],
        ],
    ],
];
