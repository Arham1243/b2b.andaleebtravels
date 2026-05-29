<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->boolean('vendor_discounts_enabled')->default(false)->after('hotel_discount_value');
        });

        \Illuminate\Support\Facades\DB::table('b2b_vendors')
            ->where(function ($query) {
                $query->whereNotNull('flight_discount_type')
                    ->orWhereNotNull('hotel_discount_type');
            })
            ->update(['vendor_discounts_enabled' => true]);
    }

    public function down(): void
    {
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->dropColumn('vendor_discounts_enabled');
        });
    }
};
