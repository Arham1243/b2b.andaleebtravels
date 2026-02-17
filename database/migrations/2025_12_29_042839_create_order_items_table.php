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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('tour_id')->constrained('tours')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade'); // nullable for guests
            $table->string('guest_email')->nullable();

            // Tour Details
            $table->string('tour_name');
            $table->date('booking_date');
            $table->string('time_slot');

            // Pricing
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 10, 2);

            // Pax Details (stored as JSON)
            $table->json('pax_details');

            // PrioTicket Integration
            $table->string('product_id_prio')->nullable();
            $table->string('availability_id')->nullable();
            $table->string('booking_reference')->nullable();
            $table->text('reservation_response')->nullable();

            // Status
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
