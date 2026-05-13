<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_saved_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_vendor_id')->constrained('b2b_vendors')->cascadeOnDelete();
            $table->string('title', 10);
            $table->string('first_name', 60);
            $table->string('last_name', 60);
            $table->date('dob')->nullable();
            $table->string('nationality', 4)->nullable();
            $table->string('passport_no', 20)->nullable();
            $table->date('passport_exp')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_saved_passengers');
    }
};
