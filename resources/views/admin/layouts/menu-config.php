<?php

return [
    [
        'title' => 'Dashboard',
        'icon' => 'bx bxs-home',
        'route' => route('admin.dashboard'),
    ],
    [
        'title' => 'Users',
        'icon' => 'bx bxs-group',
        'route' => route('admin.users.index'),
    ],
    [
        'title' => 'Newsletter',
        'icon' => 'bx bxs-envelope',
        'route' => route('admin.newsletters.index'),
    ],
    [
        'title' => 'Inquiries',
        'icon' => 'bx bxs-message-dots',
        'route' => route('admin.inquiries.index'),
    ],
    [
        'title' => 'Banners',
        'icon' => 'bx bxs-image-alt',
        'route' => route('admin.banners.index'),
    ],
    [
        'title' => 'Orders',
        'icon' => 'bx bxs-shopping-bag',
        'route' => route('admin.orders.index'),
    ],
    [
        'title' => 'Insurance Bookings',
        'icon' => 'bx bxs-shield',
        'route' => route('admin.travel-insurances.index'),
    ],
    [
        'title' => 'Hotel Bookings',
        'icon'  => 'bx bx-restaurant',
        'route' => route('admin.hotel-bookings.index')
    ],
    [
        'title' => 'Hotels',
        'icon'  => 'bx bx-restaurant',
        'submenu' => [
            [
                'title' => 'Manage Countries',
                'icon'  => 'bx bx-map',
                'route' => route('admin.countries.index'),
            ],
            [
                'title' => 'Manage Provinces',
                'icon'  => 'bx bx-map-alt',
                'route' => route('admin.provinces.index'),
            ],
            [
                'title' => 'Manage Locations',
                'icon'  => 'bx bx-location-plus',
                'route' => route('admin.locations.index'),
            ],
            [
                'title' => 'All Hotels',
                'icon'  => 'bx bx-restaurant',
                'route' => route('admin.hotels.index'),
            ],
        ],
    ],
    [
        'title' => 'Tours',
        'icon' => 'bx bx-world',
        'submenu' => [
            [
                'title' => 'All Tours',
                'icon' => 'bx bx-world',
                'route' => route('admin.tours.index'),
            ],
            [
                'title' => 'Categories',
                'icon' => 'bx bxs-category',
                'route' => route('admin.tour-categories.index'),
            ],
            [
                'title' => 'Reviews',
                'icon' => 'bx bxs-star',
                'route' => route('admin.tour-reviews.index'),
            ],
            [
                'title' => 'Coupons',
                'icon' => 'bx bxs-coupon',
                'route' => route('admin.coupons.index'),
            ],
            [
                'title' => 'Sync Tours',
                'icon' => 'bx bx-refresh',
                'route' => route('admin.tours.sync'),
            ],
        ],
    ],
    [
        'title' => 'Packages',
        'icon' => 'bx bxs-package',
        'submenu' => [
            [
                'title' => 'All Packages',
                'icon' => 'bx bxs-package',
                'route' => route('admin.packages.index'),
            ],
            [
                'title' => 'Categories',
                'icon' => 'bx bxs-category',
                'route' => route('admin.package-categories.index'),
            ],
            [
                'title' => 'Inquiries',
                'icon' => 'bx bxs-message-dots',
                'route' => route('admin.package-inquiries.index'),
            ],
        ],
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
