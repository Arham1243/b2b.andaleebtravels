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
            // Drop existing columns
            $table->dropColumn(['payment_status', 'status']);

            // Recreate as simple strings
            $table->string('payment_status', 50)->default('pending')->after('total');
            $table->string('status', 50)->default('pending')->after('payment_status');
        });

        Schema::table('order_items', function (Blueprint $table) {
            // If order_items also has status enum, recreate it as string
            if (Schema::hasColumn('order_items', 'status')) {
                $table->dropColumn('status');
                $table->string('status', 50)->default('pending')->after('price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'status']);

            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending')->after('total');
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending')->after('payment_status');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending')->after('price');
        });
    }
};
