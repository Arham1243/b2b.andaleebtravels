<?php

use App\Models\B2bVendor;
use App\Support\VendorWalletCredit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->decimal('credit_used', 12, 2)->default(0)->after('credit_limit');
        });

        B2bVendor::query()->each(function (B2bVendor $vendor) {
            VendorWalletCredit::syncVendorPools($vendor);
        });
    }

    public function down(): void
    {
        Schema::table('b2b_vendors', function (Blueprint $table) {
            $table->dropColumn('credit_used');
        });
    }
};
