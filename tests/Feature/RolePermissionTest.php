<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (!\Illuminate\Support\Facades\Schema::hasTable('tbl_departments')) {
            \Illuminate\Support\Facades\Schema::create('tbl_departments', function ($table) {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('tbl_locations')) {
            \Illuminate\Support\Facades\Schema::create('tbl_locations', function ($table) {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_employee_cannot_access_admin_routes(): void
    {
        $employee = User::factory()->create([
            'role' => 'Employee',
            'status' => 'Active',
            'permissions' => ['jobs' => 0, 'tasks' => 0, 'customers' => 0],
        ]);

        $this->actingAs($employee)
            ->get('/admin/employees')
            ->assertStatus(403);

        $this->actingAs($employee)
            ->get('/admin/departments')
            ->assertStatus(403);

        $this->actingAs($employee)
            ->get('/admin/locations')
            ->assertStatus(403);
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->create([
            'role' => 'Administrator',
            'status' => 'Active',
        ]);

        $this->actingAs($admin)
            ->get('/admin/employees')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/departments')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get('/admin/locations')
            ->assertStatus(200);
    }

    public function test_hr_manager_can_access_admin_routes(): void
    {
        $hr = User::factory()->create([
            'role' => 'HR Manager',
            'status' => 'Active',
        ]);

        $this->actingAs($hr)
            ->get('/admin/employees')
            ->assertStatus(200);
    }

    public function test_employee_without_work_access_is_forbidden_from_jobs(): void
    {
        $employee = User::factory()->create([
            'role' => 'Employee',
            'status' => 'Active',
            'permissions' => ['jobs' => 0],
        ]);

        $this->actingAs($employee)
            ->get('/work/jobs')
            ->assertStatus(403);
    }

    public function test_employee_with_view_access_can_view_jobs(): void
    {
        $employee = User::factory()->create([
            'role' => 'Employee',
            'status' => 'Active',
            'permissions' => ['jobs' => 1],
        ]);

        $this->actingAs($employee)
            ->get('/work/jobs')
            ->assertStatus(200);
    }

    public function test_manager_without_report_permission_is_forbidden(): void
    {
        $manager = User::factory()->create([
            'role' => 'Manager',
            'status' => 'Active',
            'permissions' => ['view_reports' => false],
        ]);

        $this->actingAs($manager)
            ->get('/reports')
            ->assertStatus(403);
    }

    public function test_manager_with_report_permission_can_access(): void
    {
        $manager = User::factory()->create([
            'role' => 'Manager',
            'status' => 'Active',
            'permissions' => ['view_reports' => true],
        ]);

        $this->actingAs($manager)
            ->get('/reports')
            ->assertStatus(200);
    }

    public function test_employee_without_quotes_access_is_forbidden(): void
    {
        $employee = User::factory()->create([
            'role' => 'Employee',
            'status' => 'Active',
            'permissions' => ['quotes' => 0],
        ]);

        $this->actingAs($employee)
            ->get('/work/quotes')
            ->assertStatus(403);
    }

    public function test_employee_with_quotes_access_can_view(): void
    {
        $employee = User::factory()->create([
            'role' => 'Employee',
            'status' => 'Active',
            'permissions' => ['quotes' => 1],
        ]);

        $this->actingAs($employee)
            ->get('/work/quotes')
            ->assertStatus(200);
    }

    public function test_employee_without_invoices_access_is_forbidden(): void
    {
        $employee = User::factory()->create([
            'role' => 'Employee',
            'status' => 'Active',
            'permissions' => ['invoices' => 0],
        ]);

        $this->actingAs($employee)
            ->get('/work/invoices')
            ->assertStatus(403);
    }

    public function test_employee_with_invoices_access_can_view(): void
    {
        $employee = User::factory()->create([
            'role' => 'Employee',
            'status' => 'Active',
            'permissions' => ['invoices' => 1],
        ]);

        $this->actingAs($employee)
            ->get('/work/invoices')
            ->assertStatus(200);
    }
}
