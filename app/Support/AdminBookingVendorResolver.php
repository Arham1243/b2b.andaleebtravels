<?php

namespace App\Support;

use App\Models\B2bAdmin;
use App\Models\B2bVendor;
use Illuminate\Support\Facades\Hash;

final class AdminBookingVendorResolver
{
    /** @var array<string, array{agent_code: string, name: string, username: string, email: string}> */
    private const PROFILES = [
        'super' => [
            'agent_code' => 'ADMINBOOKING',
            'name' => 'Andaleeb Travel Agency',
            'username' => 'Andaleeb Travel Agency',
            'email' => 'admin@andaleebtravels.com',
        ],
        'staff' => [
            'agent_code' => 'STAFFBOOKING',
            'name' => 'Staff Booking',
            'username' => 'Staff Booking',
            'email' => 'staff-booking@andaleebtravels.com',
        ],
    ];

    public static function resolve(?B2bAdmin $admin): B2bVendor
    {
        $profileKey = ($admin !== null && $admin->isSuperAdmin()) ? 'super' : 'staff';

        return self::getOrCreate(self::PROFILES[$profileKey]);
    }

    /**
     * @param  array{agent_code: string, name: string, username: string, email: string}  $profile
     */
    private static function getOrCreate(array $profile): B2bVendor
    {
        $vendor = B2bVendor::query()->where('agent_code', $profile['agent_code'])->first();
        if ($vendor) {
            return $vendor;
        }

        return B2bVendor::create([
            'name' => $profile['name'],
            'email' => $profile['email'],
            'username' => $profile['username'],
            'agent_code' => $profile['agent_code'],
            'password' => Hash::make('12345678'),
            'status' => 'active',
        ]);
    }
}
