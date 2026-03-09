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
        Schema::table('b2b_hotel_bookings', function (Blueprint $table) {
            $table->decimal('wallet_amount', 10, 2)->default(0)->after('total_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('b2b_hotel_bookings', function (Blueprint $table) {
            $table->dropColumn('wallet_amount');
        });
    }
};
