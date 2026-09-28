<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'managed_team_type')) {
                // 'all' or 'restricted'
                $table->string('managed_team_type')->default('all')->after('permissions');
            }
            if (!Schema::hasColumn('users', 'managed_departments')) {
                $table->json('managed_departments')->nullable()->after('managed_team_type');
            }
            if (!Schema::hasColumn('users', 'managed_locations')) {
                $table->json('managed_locations')->nullable()->after('managed_departments');
            }
            if (!Schema::hasColumn('users', 'managed_employees')) {
                $table->json('managed_employees')->nullable()->after('managed_locations');
            }
            if (!Schema::hasColumn('users', 'manager_permissions')) {
                $table->json('manager_permissions')->nullable()->after('managed_employees');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'managed_team_type',
                'managed_departments',
                'managed_locations',
                'managed_employees',
                'manager_permissions'
            ]);
        });
    }
};
