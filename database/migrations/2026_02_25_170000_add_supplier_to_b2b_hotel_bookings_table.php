<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_hotel_bookings', function (Blueprint $table) {
            $table->string('supplier')->default('yalago')->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('b2b_hotel_bookings', function (Blueprint $table) {
            $table->dropColumn('supplier');
        });
    }
};
