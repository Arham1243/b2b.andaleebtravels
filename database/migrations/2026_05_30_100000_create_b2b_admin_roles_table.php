<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 64)->unique();
            $table->boolean('is_super')->default(false);
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        Schema::table('b2b_admins', function (Blueprint $table) {
            $table->string('role', 32)->default('admin')->after('password');
            $table->json('permissions')->nullable()->after('role');
            $table->foreignId('admin_role_id')->nullable()->after('permissions')->constrained('b2b_admin_roles')->nullOnDelete();
            $table->string('status', 16)->default('active')->after('admin_role_id');
            $table->rememberToken()->after('status');
        });

        $now = now();

        $superId = DB::table('b2b_admin_roles')->insertGetId([
            'name' => 'Super Administrator',
            'slug' => 'super_admin',
            'is_super' => true,
            'permissions' => json_encode([]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('b2b_admins')->update([
            'admin_role_id' => $superId,
            'role' => 'admin',
            'status' => 'active',
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::table('b2b_admins', function (Blueprint $table) {
            $table->dropForeign(['admin_role_id']);
            $table->dropColumn(['role', 'permissions', 'admin_role_id', 'status', 'remember_token']);
        });

        Schema::dropIfExists('b2b_admin_roles');
    }
};
