<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_flight_bookings', function (Blueprint $table) {
            $table->json('ticket_numbers')->nullable()->after('ticket_response');
        });
    }

    public function down(): void
    {
        Schema::table('b2b_flight_bookings', function (Blueprint $table) {
            $table->dropColumn('ticket_numbers');
        });
    }
};
