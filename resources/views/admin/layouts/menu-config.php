<?php

return [
    [
        'title' => 'Dashboard',
        'icon' => 'bx bxs-home',
        'route' => route('admin.dashboard'),
    ],
    [
        'title' => 'Vendors',
        'icon' => 'bx bxs-briefcase',
        'route' => route('admin.vendors.index'),
    ],
    [
        'title' => 'Hotel Booking',
        'icon' => 'bx bxs-hotel',
        'route' => route('admin.hotels.start'),
    ],
    [
        'title' => 'Flight Booking',
        'icon' => 'bx bxs-plane',
        'route' => route('admin.flights.start'),
    ],
    [
        'title' => 'Inquiries',
        'icon' => 'bx bxs-message-dots',
        'route' => route('admin.inquiries.index'),
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
