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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('order_number')->unique();
            
            // Passenger Details
            $table->string('passenger_title')->nullable();
            $table->string('passenger_first_name');
            $table->string('passenger_last_name');
            $table->string('passenger_email');
            $table->string('passenger_phone');
            $table->string('passenger_country')->nullable();
            $table->string('passenger_address')->nullable();
            $table->text('passenger_special_request')->nullable();
            
            // Payment Details
            $table->string('payment_method'); // card, tabby
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            
            // Order Amounts
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('vat', 10, 2)->default(0);
            $table->decimal('service_tax', 10, 2)->default(0);
            $table->decimal('tabby_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            
            // Coupon Details
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->onDelete('set null');
            $table->string('coupon_code')->nullable();
            $table->decimal('coupon_discount', 10, 2)->default(0);
            
            // Order Status
            $table->enum('status', ['pending', 'confirmed', 'processing', 'completed', 'cancelled'])->default('pending');
            
            // External References
            $table->string('reservation_reference')->nullable();
            $table->text('reservation_data')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
