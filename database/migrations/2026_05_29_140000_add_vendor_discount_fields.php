<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->string('flight_discount_type', 20)->nullable()->after('flight_search_providers');
            $table->decimal('flight_discount_value', 10, 2)->default(0)->after('flight_discount_type');
            $table->string('hotel_discount_type', 20)->nullable()->after('flight_discount_value');
            $table->decimal('hotel_discount_value', 10, 2)->default(0)->after('hotel_discount_type');
        });

        Schema::table('b2b_flight_bookings', function (Blueprint $table) {
            $table->decimal('original_amount', 10, 2)->nullable()->after('total_amount');
            $table->decimal('vendor_discount_amount', 10, 2)->default(0)->after('original_amount');
            $table->json('vendor_discount_snapshot')->nullable()->after('vendor_discount_amount');
        });

        Schema::table('b2b_hotel_bookings', function (Blueprint $table) {
            $table->decimal('original_amount', 10, 2)->nullable()->after('total_amount');
            $table->decimal('vendor_discount_amount', 10, 2)->default(0)->after('original_amount');
            $table->json('vendor_discount_snapshot')->nullable()->after('vendor_discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('b2b_hotel_bookings', function (Blueprint $table) {
            $table->dropColumn(['original_amount', 'vendor_discount_amount', 'vendor_discount_snapshot']);
        });

        Schema::table('b2b_flight_bookings', function (Blueprint $table) {
            $table->dropColumn(['original_amount', 'vendor_discount_amount', 'vendor_discount_snapshot']);
        });

        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->dropColumn([
                'flight_discount_type',
                'flight_discount_value',
                'hotel_discount_type',
                'hotel_discount_value',
            ]);
        });
    }
};
