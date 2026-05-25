<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('configs') || ! Schema::hasColumn('configs', 'config_value')) {
            return;
        }

        DB::statement('ALTER TABLE `configs` MODIFY `config_value` TEXT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('configs') || ! Schema::hasColumn('configs', 'config_value')) {
            return;
        }

        DB::statement('ALTER TABLE `configs` MODIFY `config_value` VARCHAR(255) NULL');
    }
};
