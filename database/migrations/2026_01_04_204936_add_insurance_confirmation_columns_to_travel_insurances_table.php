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
        Schema::table('travel_insurances', function (Blueprint $table) {
            $table->string('payby_merchant_order_no')->nullable()->after('payment_reference');
            $table->string('payby_order_no')->nullable()->after('payby_merchant_order_no');
            $table->text('payby_payment_response')->nullable()->after('payment_response');
            $table->string('proposal_state')->nullable()->after('payby_payment_response');
            $table->text('policy_numbers')->nullable()->after('proposal_state');
            $table->text('confirmed_passengers')->nullable()->after('policy_numbers');
            $table->text('error_messages')->nullable()->after('confirmed_passengers');
            $table->boolean('booking_confirmed')->default(false)->after('error_messages');
            $table->text('confirmation_response')->nullable()->after('booking_confirmed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_insurances', function (Blueprint $table) {
            $table->dropColumn([
                'payby_merchant_order_no',
                'payby_order_no',
                'payby_payment_response',
                'proposal_state',
                'policy_numbers',
                'confirmed_passengers',
                'error_messages',
                'booking_confirmed',
                'confirmation_response'
            ]);
        });
    }
};
