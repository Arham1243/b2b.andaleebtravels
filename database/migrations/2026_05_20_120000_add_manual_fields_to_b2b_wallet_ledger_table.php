<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_wallet_ledger', function (Blueprint $table) {
            $table->boolean('is_manual')->default(false)->after('description');
            $table->foreignId('recorded_by_b2b_admin_id')
                ->nullable()
                ->after('is_manual')
                ->constrained('b2b_admins')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('b2b_wallet_ledger', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by_b2b_admin_id');
            $table->dropColumn('is_manual');
        });
    }
};
