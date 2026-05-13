<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_flight_bookings', function (Blueprint $table) {
            $table->timestamp('confirmation_email_sent_at')->nullable()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('b2b_flight_bookings', function (Blueprint $table) {
            $table->dropColumn('confirmation_email_sent_at');
        });
    }
};
