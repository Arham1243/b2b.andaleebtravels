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
        Schema::table('order_items', function (Blueprint $table) {
            // Add columns for storing PrioTicket reservation and order data
            $table->text('reservation_data')->nullable()->after('reservation_response');
            $table->text('order_data')->nullable()->after('reservation_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['reservation_data', 'order_data']);
        });
    }
};
