<?php

/**
 * admin.bulk-actions: resource slug from URL => bulk action key => granular permission required.
 */
return [
    'vendors' => [
        'delete' => 'vendors_delete',
        'active' => 'vendors_edit',
        'inactive' => 'vendors_edit',
    ],
    'inquiries' => [
        'delete' => 'inquiries_delete',
    ],
];
