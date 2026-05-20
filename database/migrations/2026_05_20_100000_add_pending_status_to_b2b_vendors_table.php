<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE b2b_vendors MODIFY COLUMN status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("UPDATE b2b_vendors SET status = 'inactive' WHERE status = 'pending'");
        DB::statement("ALTER TABLE b2b_vendors MODIFY COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
    }
};
