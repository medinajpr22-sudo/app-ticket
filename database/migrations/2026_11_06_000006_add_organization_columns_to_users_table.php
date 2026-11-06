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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->after('id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->after('role_id')->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->after('location_id')->constrained('users')->nullOnDelete();
            $table->string('job_title')->nullable()->after('email');
            $table->string('phone', 30)->nullable()->after('job_title');
            $table->string('password')->nullable()->change();
            $table->string('external_id')->nullable()->unique()->after('password');
            $table->boolean('is_active')->default(true)->after('remember_token');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_id');
            $table->dropConstrainedForeignId('location_id');
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('role_id');
            $table->dropUnique(['external_id']);
            $table->dropColumn(['job_title', 'phone', 'external_id', 'is_active', 'last_login_at']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
