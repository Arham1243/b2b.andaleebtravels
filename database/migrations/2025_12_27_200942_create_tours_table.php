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
        Schema::create('tours', function (Blueprint $table) {
            $table->id();

            $table->string('distributer_name')->nullable();
            $table->unsignedBigInteger('distributer_id')->nullable();

            $table->string('type')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            
            $table->float('price')->nullable();
            $table->float('discount_price')->nullable();
            $table->float('child_price')->nullable();
            $table->unsignedBigInteger('min_qty')->nullable();
            $table->unsignedBigInteger('max_qty')->nullable();
            
            $table->string('duration')->nullable();
            $table->json('content')->nullable();
            $table->text('short_description')->nullable();
            $table->text('long_description')->nullable();
            $table->text('additional_information')->nullable();
            $table->text('cancellation_policies')->nullable();
            
            $table->json('locations')->nullable();
            $table->json('includes')->nullable();
            $table->json('excludes')->nullable();
            $table->json('product_type_seasons')->nullable();

            $table->boolean('is_featured')->default(0);
            $table->boolean('is_recommended')->default(0);

            $table->string('status')->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
