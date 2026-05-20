<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->string('travel_agency')->nullable()->after('name');
            $table->string('first_name')->nullable()->after('travel_agency');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('designation')->nullable()->after('email');
            $table->string('trade_license_number')->nullable()->after('designation');
            $table->date('trade_license_expiry')->nullable()->after('trade_license_number');
        });
    }

    public function down(): void
    {
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->dropColumn([
                'travel_agency',
                'first_name',
                'last_name',
                'designation',
                'trade_license_number',
                'trade_license_expiry',
            ]);
        });
    }
};
