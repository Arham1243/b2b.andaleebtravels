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
        Schema::create('travel_insurances', function (Blueprint $table) {
            $table->id();
            $table->string('insurance_number')->unique();
            $table->string('plan_title')->nullable();
            $table->string('plan_code')->nullable();
            $table->string('ssr_fee_code')->nullable();
            $table->string('channel')->nullable();
            $table->string('pnr')->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('currency', 10)->default('AED');
            $table->decimal('total_premium', 10, 2)->default(0);
            $table->string('country_code', 10)->nullable();
            $table->integer('total_adults')->default(0);
            $table->integer('total_children')->default(0);
            $table->integer('total_infants')->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('payment_status')->default('pending');
            $table->text('payment_response')->nullable();
            $table->string('tabby_payment_id')->nullable();
            $table->string('lead_name')->nullable();
            $table->string('lead_email')->nullable();
            $table->string('lead_phone')->nullable();
            $table->string('lead_country_of_residence')->nullable();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->date('start_date')->nullable();
            $table->date('return_date')->nullable();
            $table->string('residence_country')->nullable();
            $table->text('request_data')->nullable();
            $table->text('api_response')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_insurances');
    }
};
