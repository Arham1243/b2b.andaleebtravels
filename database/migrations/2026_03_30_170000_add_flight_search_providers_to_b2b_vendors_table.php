<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->json('flight_search_providers')->nullable()->after('hotel_search_providers');
        });
    }

    public function down(): void
    {
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->dropColumn('flight_search_providers');
        });
    }
};
