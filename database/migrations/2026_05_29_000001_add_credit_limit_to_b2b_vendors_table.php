<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->decimal('credit_limit', 12, 2)->default(0)->after('main_balance');
        });
    }

    public function down(): void
    {
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->dropColumn('credit_limit');
        });
    }
};
