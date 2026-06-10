<?php

use App\Support\SupportContact;

return [
    'agency_name' => env('ETICKET_AGENCY_NAME', config('app.name', 'Andaleeb Travel Agency')),
    'agency_legal_name' => env('ETICKET_AGENCY_LEGAL_NAME', 'Andaleeb Travel Agency FZE'),
    'address' => env('ETICKET_AGENCY_ADDRESS', 'S 18 Building V05, Russia Cluster, International City, Dubai'),
    'country' => env('ETICKET_AGENCY_COUNTRY', 'United Arab Emirates'),
    'phone' => env('ETICKET_AGENCY_PHONE', SupportContact::DEFAULT_WHATSAPP),
    'email' => env('ETICKET_AGENCY_EMAIL', SupportContact::DEFAULT_EMAIL),
    'logo_path' => public_path('frontend/assets/images/logo.png'),
];
