<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_flight_bookings', function (Blueprint $table) {
            $table->timestamp('hold_expires_at')->nullable()->after('booking_status');
        });

        // Backfill existing hold bookings: estimate expiry as created_at + 1 hour
        DB::table('b2b_flight_bookings')
            ->where('booking_status', 'hold')
            ->whereNull('hold_expires_at')
            ->update([
                'hold_expires_at' => DB::raw('DATE_ADD(created_at, INTERVAL 1 HOUR)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('b2b_flight_bookings', function (Blueprint $table) {
            $table->dropColumn('hold_expires_at');
        });
    }
};
