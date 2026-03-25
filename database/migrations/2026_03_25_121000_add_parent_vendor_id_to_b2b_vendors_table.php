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
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->foreignId('parent_vendor_id')->nullable()->after('id')->constrained('b2b_vendors')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_vendor_id');
        });
    }
};
