<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('b2b_hotel_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_vendor_id')->constrained('b2b_vendors')->cascadeOnDelete();
            $table->string('booking_number')->unique();
            $table->string('yalago_booking_reference')->nullable();
            $table->string('yalago_hotel_id');
            $table->string('hotel_name');
            $table->string('hotel_address')->nullable();
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->unsignedInteger('nights');
            $table->json('rooms_data')->nullable();
            $table->json('selected_rooms')->nullable();
            $table->string('lead_title')->nullable();
            $table->string('lead_first_name');
            $table->string('lead_last_name');
            $table->string('lead_email');
            $table->string('lead_phone')->nullable();
            $table->text('lead_address')->nullable();
            $table->json('guests_data')->nullable();
            $table->json('extras_data')->nullable();
            $table->decimal('extras_total', 10, 2)->default(0);
            $table->json('flight_details')->nullable();
            $table->decimal('rooms_total', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency', 10)->default('AED');
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('payment_reference')->nullable();
            $table->string('tabby_payment_id')->nullable();
            $table->json('payment_response')->nullable();
            $table->string('booking_status')->default('pending');
            $table->json('availability_request')->nullable();
            $table->json('availability_response')->nullable();
            $table->json('booking_request')->nullable();
            $table->json('booking_response')->nullable();
            $table->string('source_market', 10)->default('AE');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b2b_hotel_bookings');
    }
};
