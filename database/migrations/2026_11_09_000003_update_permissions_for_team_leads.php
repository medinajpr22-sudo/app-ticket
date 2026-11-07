<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Internal notes left the chat, so their permission goes away. Team leads get
 * a permission of their own, granted to the admin role where it already exists.
 * Fresh installations receive it from the RolePermissionSeeder instead.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('permissions')->where('slug', 'tickets.add_internal_note')->delete();

            $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');

            if ($adminRoleId === null || DB::table('permissions')->where('slug', 'teams.lead')->exists()) {
                return;
            }

            $permissionId = DB::table('permissions')->insertGetId([
                'slug' => 'teams.lead',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('permission_role')->insert(['permission_id' => $permissionId, 'role_id' => $adminRoleId]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')->where('slug', 'teams.lead')->delete();
    }
};
