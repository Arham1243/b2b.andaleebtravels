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
        Schema::table('packages', function (Blueprint $table) {
            $table->boolean('is_featured')->nullable()->default(0)->after('status');
            $table->unsignedInteger('nights')->nullable()->after('is_featured');
            $table->unsignedInteger('days')->nullable()->after('nights');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('is_featured');
            $table->dropColumn('nights');
            $table->dropColumn('days');
        });
    }
};
