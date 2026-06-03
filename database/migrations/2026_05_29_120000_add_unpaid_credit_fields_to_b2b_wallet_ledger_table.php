<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_wallet_ledger', function (Blueprint $table) {
            $table->string('manual_kind', 50)->nullable()->after('is_manual');
            $table->foreignId('settles_ledger_id')
                ->nullable()
                ->after('manual_kind')
                ->constrained('b2b_wallet_ledger')
                ->nullOnDelete();

            $table->index(['b2b_vendor_id', 'manual_kind', 'voided_at'], 'b2b_wallet_ledger_vendor_kind_void_idx');
        });
    }

    public function down(): void
    {
        Schema::table('b2b_wallet_ledger', function (Blueprint $table) {
            $table->dropIndex('b2b_wallet_ledger_vendor_kind_void_idx');
            $table->dropConstrainedForeignId('settles_ledger_id');
            $table->dropColumn('manual_kind');
        });
    }
};
