<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_hotel_bookings', function (Blueprint $table) {
            $table->json('cancel_response')->nullable()->after('booking_response');
        });
    }

    public function down(): void
    {
        Schema::table('b2b_hotel_bookings', function (Blueprint $table) {
            $table->dropColumn('cancel_response');
        });
    }
};
