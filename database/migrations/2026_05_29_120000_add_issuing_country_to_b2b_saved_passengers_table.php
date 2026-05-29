<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_saved_passengers', function (Blueprint $table) {
            $table->string('issuing_country', 4)->nullable()->after('nationality');
        });
    }

    public function down(): void
    {
        Schema::table('b2b_saved_passengers', function (Blueprint $table) {
            $table->dropColumn('issuing_country');
        });
    }
};
