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
        Schema::table('travel_insurance_passengers', function (Blueprint $table) {
            $table->string('policy_number')->nullable()->after('status');
            $table->text('policy_url_link')->nullable()->after('policy_number');
            $table->text('insurance_details')->nullable()->after('policy_url_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_insurance_passengers', function (Blueprint $table) {
            $table->dropColumn(['policy_number', 'policy_url_link', 'insurance_details']);
        });
    }
};
