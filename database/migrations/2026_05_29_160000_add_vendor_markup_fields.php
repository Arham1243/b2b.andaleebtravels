<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->boolean('vendor_markups_enabled')->default(false)->after('vendor_discounts_enabled');
            $table->string('flight_markup_type', 20)->nullable()->after('vendor_markups_enabled');
            $table->decimal('flight_markup_value', 10, 2)->default(0)->after('flight_markup_type');
            $table->string('hotel_markup_type', 20)->nullable()->after('flight_markup_value');
            $table->decimal('hotel_markup_value', 10, 2)->default(0)->after('hotel_markup_type');
            $table->boolean('agent_markup_override_enabled')->default(false)->after('hotel_markup_value');
            $table->string('agent_flight_markup_type', 20)->nullable()->after('agent_markup_override_enabled');
            $table->decimal('agent_flight_markup_value', 10, 2)->default(0)->after('agent_flight_markup_type');
            $table->string('agent_hotel_markup_type', 20)->nullable()->after('agent_flight_markup_value');
            $table->decimal('agent_hotel_markup_value', 10, 2)->default(0)->after('agent_hotel_markup_type');
        });

        Schema::table('b2b_flight_bookings', function (Blueprint $table) {
            $table->decimal('vendor_markup_amount', 10, 2)->default(0)->after('vendor_discount_snapshot');
            $table->json('vendor_markup_snapshot')->nullable()->after('vendor_markup_amount');
        });

        Schema::table('b2b_hotel_bookings', function (Blueprint $table) {
            $table->decimal('vendor_markup_amount', 10, 2)->default(0)->after('vendor_discount_snapshot');
            $table->json('vendor_markup_snapshot')->nullable()->after('vendor_markup_amount');
        });
    }

    public function down(): void
    {
        Schema::table('b2b_hotel_bookings', function (Blueprint $table) {
            $table->dropColumn(['vendor_markup_amount', 'vendor_markup_snapshot']);
        });

        Schema::table('b2b_flight_bookings', function (Blueprint $table) {
            $table->dropColumn(['vendor_markup_amount', 'vendor_markup_snapshot']);
        });

        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->dropColumn([
                'vendor_markups_enabled',
                'flight_markup_type',
                'flight_markup_value',
                'hotel_markup_type',
                'hotel_markup_value',
                'agent_markup_override_enabled',
                'agent_flight_markup_type',
                'agent_flight_markup_value',
                'agent_hotel_markup_type',
                'agent_hotel_markup_value',
            ]);
        });
    }
};
