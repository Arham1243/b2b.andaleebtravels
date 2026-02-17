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
        Schema::create('hotel_bookings', function (Blueprint $table) {
            $table->id();

            // User relationship (nullable for guest bookings)
            $table->unsignedInteger('user_id')->nullable();

            // Booking reference
            $table->string('booking_number')->unique();
            $table->string('yalago_booking_reference')->nullable();

            // Hotel information
            $table->unsignedInteger('yalago_hotel_id');
            $table->string('hotel_name');
            $table->text('hotel_address')->nullable();

            // Booking dates
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('nights');

            // Room details
            $table->json('rooms_data'); // Stores room configuration (adults, children, ages)
            $table->json('selected_rooms'); // Stores selected room details (codes, names, prices)

            // Guest information (lead guest)
            $table->string('lead_title')->nullable();
            $table->string('lead_first_name');
            $table->string('lead_last_name');
            $table->string('lead_email');
            $table->string('lead_phone');
            $table->text('lead_address')->nullable();

            // Additional guests
            $table->json('guests_data')->nullable();

            // Extras/Transfers
            $table->json('extras_data')->nullable();
            $table->decimal('extras_total', 10, 2)->default(0);

            // Flight details
            $table->json('flight_details')->nullable();

            // Pricing
            $table->decimal('rooms_total', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency')->default('AED');

            // Payment information
            $table->string('payment_method'); // payby, tabby
            $table->string('payment_status')->default('pending'); // pending, paid, failed, refunded
            $table->string('payment_reference')->nullable();
            $table->string('tabby_payment_id')->nullable();
            $table->text('payment_response')->nullable();

            // Booking status
            $table->string('booking_status')->default('pending'); // pending, confirmed, cancelled, failed

            // API request/response data
            $table->json('availability_request')->nullable();
            $table->json('availability_response')->nullable();
            $table->json('booking_request')->nullable();
            $table->json('booking_response')->nullable();

            // Source market
            $table->string('source_market')->default('AE');

            // Metadata
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('lead_email');
            $table->index('booking_number');
            $table->index('payment_status');
            $table->index('booking_status');
            $table->index(['check_in_date', 'check_out_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_bookings');
    }
};
