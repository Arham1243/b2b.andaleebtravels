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
            $table->text('prio_cancel_response')->nullable()->after('prio_order_response');
            $table->timestamp('cancelled_at')->nullable()->after('prio_cancel_response');
            $table->string('cancelled_by')->nullable()->after('cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['prio_cancel_response', 'cancelled_at', 'cancelled_by']);
        });
    }
};
