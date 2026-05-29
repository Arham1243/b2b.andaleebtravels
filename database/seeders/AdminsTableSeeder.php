<?php

namespace Database\Seeders;

use App\Models\B2bAdmin;
use App\Models\B2bAdminRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminsTableSeeder extends Seeder
{
    public function run(): void
    {
        $superRole = B2bAdminRole::query()->where('slug', 'super_admin')->first();

        B2bAdmin::query()->updateOrCreate(
            ['email' => 'admin@andaleebtravels.com'],
            [
                'name' => 'admin',
                'password' => Hash::make('12345678'),
                'role' => 'admin',
                'status' => B2bAdmin::STATUS_ACTIVE,
                'admin_role_id' => $superRole?->id,
            ],
        );
    }
}
