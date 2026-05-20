<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->dropUnique(['agent_code']);
            $table->index('agent_code');
        });
    }

    public function down(): void
    {
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->dropIndex(['agent_code']);
            $table->unique('agent_code');
        });
    }
};
