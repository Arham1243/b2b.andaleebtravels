<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->string('agency_logo')->nullable()->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->dropColumn('agency_logo');
        });
    }
};
