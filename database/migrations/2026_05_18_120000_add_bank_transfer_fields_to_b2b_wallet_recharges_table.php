<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_wallet_recharges', function (Blueprint $table) {
            $table->string('proof_image_path')->nullable()->after('tabby_payment_id');
            $table->foreignId('confirmed_by_b2b_admin_id')->nullable()->after('paid_at')->constrained('b2b_admins')->nullOnDelete();
            $table->timestamp('admin_confirmed_at')->nullable()->after('confirmed_by_b2b_admin_id');
        });
    }

    public function down(): void
    {
        Schema::table('b2b_wallet_recharges', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by_b2b_admin_id']);
            $table->dropColumn(['proof_image_path', 'confirmed_by_b2b_admin_id', 'admin_confirmed_at']);
        });
    }
};
