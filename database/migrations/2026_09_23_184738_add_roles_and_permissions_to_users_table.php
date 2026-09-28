<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'display_id')) {
                $table->string('display_id')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('Employee')->after('display_id'); // Administrator, Manager, Employee
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('Active')->after('role'); // Active, Inactive
            }
            if (!Schema::hasColumn('users', 'color')) {
                $table->string('color')->default('#005a9c')->after('status');
            }
            if (!Schema::hasColumn('users', 'base_pay')) {
                $table->decimal('base_pay', 10, 2)->nullable()->after('color');
            }
            if (!Schema::hasColumn('users', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('base_pay');
            }
            if (!Schema::hasColumn('users', 'location_id')) {
                $table->unsignedBigInteger('location_id')->nullable()->after('department_id');
            }
            if (!Schema::hasColumn('users', 'permissions')) {
                $table->json('permissions')->nullable()->after('location_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'last_name', 'phone', 'display_id',
                'role', 'status', 'color', 'base_pay',
                'department_id', 'location_id', 'permissions'
            ]);
        });
    }
};
