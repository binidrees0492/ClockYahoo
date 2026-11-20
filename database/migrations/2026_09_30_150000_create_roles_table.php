<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_system')->default(false);
                $table->string('status')->default('Active'); // Active, Inactive
                $table->json('permissions')->nullable();
                $table->timestamps();
            });
        }

        // Seed initial system roles
        $systemRoles = [
            [
                'name'        => 'Super Admin',
                'slug'        => 'super-admin',
                'description' => 'Unrestricted master account. Manages company identity, server configurations, UAM, and custom fonts. Invisible in directory lists.',
                'is_system'   => true,
                'status'      => 'Active',
                'permissions' => json_encode(\App\Models\Role::getDefaultPermissionsForRole('Super Admin')),
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Administrator',
                'slug'        => 'administrator',
                'description' => 'Full administrative management across employees, operations, and day-to-day settings.',
                'is_system'   => true,
                'status'      => 'Active',
                'permissions' => json_encode(\App\Models\Role::getDefaultPermissionsForRole('Administrator')),
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'HR Manager',
                'slug'        => 'hr-manager',
                'description' => 'People management, leave policies, attendance review, and staff directories.',
                'is_system'   => true,
                'status'      => 'Active',
                'permissions' => json_encode(\App\Models\Role::getDefaultPermissionsForRole('HR Manager')),
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Manager',
                'slug'        => 'manager',
                'description' => 'Team lead access. Approves timesheets, assigns shifts, and monitors operational tasks.',
                'is_system'   => true,
                'status'      => 'Active',
                'permissions' => json_encode(\App\Models\Role::getDefaultPermissionsForRole('Manager')),
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Employee',
                'slug'        => 'employee',
                'description' => 'Standard employee user. Clocks in/out, tracks timesheets, and checks assigned shifts.',
                'is_system'   => true,
                'status'      => 'Active',
                'permissions' => json_encode(\App\Models\Role::getDefaultPermissionsForRole('Employee')),
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        foreach ($systemRoles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                $role
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
