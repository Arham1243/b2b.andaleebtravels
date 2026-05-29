<?php

/**
 * B2B portal permission UI (assigned to custom roles). Super Administrator bypasses all checks.
 */
return [
    'groups' => [
        [
            'id' => 'vendors',
            'heading' => 'Vendors & agencies',
            'subheading' => 'Agency accounts, signup requests, wallet adjustments.',
            'items' => [
                'vendors_view' => ['label' => 'Vendors - view', 'help' => 'List and view vendor accounts.'],
                'vendors_add' => ['label' => 'Vendors - add', 'help' => 'Create new vendor accounts.'],
                'vendors_edit' => ['label' => 'Vendors - edit', 'help' => 'Edit vendors, sub-agents, status changes.'],
                'vendors_delete' => ['label' => 'Vendors - delete', 'help' => 'Delete vendors and bulk delete.'],
                'vendors_pending_view' => ['label' => 'Signup requests - view', 'help' => 'View pending vendor signups.'],
                'vendors_pending_manage' => ['label' => 'Signup requests - manage', 'help' => 'Approve or reject signup requests.'],
                'vendors_wallet_manage' => ['label' => 'Vendor wallet - manage', 'help' => 'Wallet ledger entries, payment reminders.'],
            ],
        ],
        [
            'id' => 'bookings',
            'heading' => 'Bookings',
            'subheading' => 'Hotel and flight bookings from B2B agents.',
            'items' => [
                'hotel_bookings_view' => ['label' => 'Hotel bookings - view', 'help' => 'List and view hotel bookings.'],
                'hotel_bookings_edit' => ['label' => 'Hotel bookings - manage', 'help' => 'Update status and cancel hotel bookings.'],
                'flight_bookings_view' => ['label' => 'Flight bookings - view', 'help' => 'List and view flight bookings.'],
                'flight_bookings_edit' => ['label' => 'Flight bookings - manage', 'help' => 'Update status, release hold, cancel flights.'],
            ],
        ],
        [
            'id' => 'inquiries',
            'heading' => 'Inquiries',
            'subheading' => 'Contact and lead messages from the B2B portal.',
            'items' => [
                'inquiries_view' => ['label' => 'Inquiries - view', 'help' => 'View inquiry list.'],
                'inquiries_delete' => ['label' => 'Inquiries - delete', 'help' => 'Delete inquiries and bulk delete.'],
            ],
        ],
        [
            'id' => 'wallet',
            'heading' => 'Wallet bank transfers',
            'subheading' => 'Agent wallet top-ups via bank transfer.',
            'items' => [
                'wallet_transfers_view' => ['label' => 'Bank transfers - view', 'help' => 'View pending and processed transfers.'],
                'wallet_transfers_manage' => ['label' => 'Bank transfers - manage', 'help' => 'Confirm or reject bank transfer requests.'],
            ],
        ],
        [
            'id' => 'settings',
            'heading' => 'Site settings',
            'subheading' => 'Logo, contact details, promo boxes.',
            'items' => [
                'settings_view' => ['label' => 'Settings - view', 'help' => 'Open logo and configuration pages.'],
                'settings_edit' => ['label' => 'Settings - edit', 'help' => 'Save logo and site configuration.'],
            ],
        ],
        [
            'id' => 'portal_staff',
            'heading' => 'Portal administrators',
            'subheading' => 'Internal staff accounts and role templates.',
            'items' => [
                'portal_admins_view' => ['label' => 'Portal admins - view', 'help' => 'List portal administrators.'],
                'portal_admins_add' => ['label' => 'Portal admins - add', 'help' => 'Create administrators and send setup email.'],
                'portal_admins_edit' => ['label' => 'Portal admins - edit', 'help' => 'Edit administrator accounts.'],
                'portal_admins_delete' => ['label' => 'Portal admins - delete', 'help' => 'Remove administrator accounts.'],
                'portal_roles_view' => ['label' => 'Portal roles - view', 'help' => 'List custom roles.'],
                'portal_roles_add' => ['label' => 'Portal roles - add', 'help' => 'Create custom roles.'],
                'portal_roles_edit' => ['label' => 'Portal roles - edit', 'help' => 'Edit role permissions.'],
                'portal_roles_delete' => ['label' => 'Portal roles - delete', 'help' => 'Delete unused roles.'],
            ],
        ],
    ],
];
