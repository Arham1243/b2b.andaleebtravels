<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_wallet_recharges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_vendor_id')->constrained('b2b_vendors')->cascadeOnDelete();
            $table->string('transaction_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('AED');
            $table->string('payment_method'); // payby, tabby
            $table->string('status')->default('pending'); // pending, paid, failed
            $table->string('payment_reference')->nullable();
            $table->string('tabby_payment_id')->nullable();
            $table->json('payment_response')->nullable();
            $table->string('failure_reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_wallet_recharges');
    }
};
