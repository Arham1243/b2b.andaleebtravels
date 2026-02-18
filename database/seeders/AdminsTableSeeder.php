<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('b2b_admins')->updateOrInsert(
            ['email' => 'admin@andaleebtravels.com'],
            [
                'name' => 'admin',
                'email' => 'admin@andaleebtravels.com',
                'password' => Hash::make('12345678'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
