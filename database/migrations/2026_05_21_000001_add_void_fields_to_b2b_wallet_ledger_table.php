<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_wallet_ledger', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('recorded_by_b2b_admin_id');
            $table->foreignId('voided_by_b2b_admin_id')
                ->nullable()
                ->after('voided_at')
                ->constrained('b2b_admins')
                ->nullOnDelete();
            $table->foreignId('modified_by_b2b_admin_id')
                ->nullable()
                ->after('voided_by_b2b_admin_id')
                ->constrained('b2b_admins')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('b2b_wallet_ledger', function (Blueprint $table) {
            $table->dropConstrainedForeignId('modified_by_b2b_admin_id');
            $table->dropConstrainedForeignId('voided_by_b2b_admin_id');
            $table->dropColumn('voided_at');
        });
    }
};
