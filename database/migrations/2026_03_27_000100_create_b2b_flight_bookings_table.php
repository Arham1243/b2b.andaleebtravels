<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_flight_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_vendor_id')->constrained('b2b_vendors')->cascadeOnDelete();
            $table->string('booking_number')->unique();
            $table->string('sabre_record_locator')->nullable();
            $table->unsignedBigInteger('itinerary_id')->nullable();

            $table->string('from_airport', 10)->nullable();
            $table->string('to_airport', 10)->nullable();
            $table->date('departure_date')->nullable();
            $table->date('return_date')->nullable();

            $table->unsignedInteger('adults')->default(1);
            $table->unsignedInteger('children')->default(0);
            $table->unsignedInteger('infants')->default(0);

            $table->json('passengers_data')->nullable();
            $table->json('itinerary_data')->nullable();
            $table->json('search_request')->nullable();
            $table->json('search_response')->nullable();
            $table->json('booking_request')->nullable();
            $table->json('booking_response')->nullable();
            $table->json('ticket_request')->nullable();
            $table->json('ticket_response')->nullable();
            $table->json('cancel_response')->nullable();

            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('wallet_amount', 10, 2)->default(0);
            $table->string('currency', 10)->default('AED');

            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('payment_reference')->nullable();
            $table->string('tabby_payment_id')->nullable();
            $table->json('payment_response')->nullable();

            $table->string('booking_status')->default('pending');
            $table->string('ticket_status')->default('pending');

            $table->string('source_market', 10)->default('AE');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_by')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_flight_bookings');
    }
};
