<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add new columns
            if (!Schema::hasColumn('orders', 'applied_coupons')) {
                $table->json('applied_coupons')->nullable()->after('total');
            }
            if (!Schema::hasColumn('orders', 'payment_response')) {
                $table->text('payment_response')->nullable()->after('payment_status');
            }
        });

        // Remove old coupon columns if they exist (replaced by applied_coupons JSON)
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'coupon_id')) {
                $table->dropColumn(['coupon_id', 'coupon_code', 'coupon_discount']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['applied_coupons', 'payment_response']);

            // Restore old coupon columns
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->onDelete('set null');
            $table->string('coupon_code')->nullable();
            $table->decimal('coupon_discount', 10, 2)->default(0);
        });
    }
};
