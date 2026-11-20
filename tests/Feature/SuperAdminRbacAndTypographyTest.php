<?php

namespace Tests\Feature;

use App\Livewire\AccessManagement;
use App\Livewire\CompanySettings;
use App\Livewire\Departments;
use App\Livewire\Employees;
use App\Livewire\TimeOff\PolicyManager;
use App\Livewire\Work\Customers;
use App\Livewire\Work\Jobs;
use App\Livewire\Work\Tasks;
use App\Models\Assignment;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Role;
use App\Models\Task;
use App\Models\AuditLog;
use App\Models\TimeLog;
use App\Models\TimeOffPolicy;
use App\Models\TimeOffPolicyEmployee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminRbacAndTypographyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed default roles
        $defaultRoles = [
            ['name' => 'Super Admin', 'slug' => 'super-admin', 'is_system' => true, 'description' => 'System Super Administrator'],
            ['name' => 'Administrator', 'slug' => 'administrator', 'is_system' => true, 'description' => 'System Administrator'],
            ['name' => 'HR Manager', 'slug' => 'hr-manager', 'is_system' => true, 'description' => 'Human Resources Manager'],
            ['name' => 'Manager', 'slug' => 'manager', 'is_system' => true, 'description' => 'Department Manager'],
            ['name' => 'Employee', 'slug' => 'employee', 'is_system' => true, 'description' => 'Staff Member'],
        ];

        foreach ($defaultRoles as $r) {
            Role::firstOrCreate(['name' => $r['name']], array_merge($r, [
                'status' => 'active',
                'permissions' => Role::getDefaultPermissionsForRole($r['name']),
            ]));
        }
    }

    public function test_super_admin_can_update_critical_company_settings(): void
    {
        $superAdmin = User::factory()->create([
            'role'   => 'Super Admin',
            'status' => 'Active',
        ]);

        $this->actingAs($superAdmin);

        Livewire::test(CompanySettings::class)
            ->set('companyName', 'Acme Global Enterprises')
            ->set('serverTimezone', 'America/New_York')
            ->call('saveGeneral')
            ->assertHasNoErrors();

        $this->assertEquals('Acme Global Enterprises', CompanySetting::get('company_name'));
        $this->assertEquals('America/New_York', CompanySetting::getServerTimezone());
    }

    public function test_standard_admin_can_alter_critical_company_name_and_timezone(): void
    {
        CompanySetting::set('company_name', 'Locked Organization');
        CompanySetting::set('server_timezone', 'UTC');

        $admin = User::factory()->create([
            'role'   => 'Administrator',
            'status' => 'Active',
        ]);

        $this->actingAs($admin);

        // Administrator should be able to update company settings
        Livewire::test(CompanySettings::class)
            ->set('companyName', 'Updated Organization')
            ->set('serverTimezone', 'Asia/Tokyo')
            ->call('saveGeneral')
            ->assertHasNoErrors();

        $this->assertEquals('Updated Organization', CompanySetting::get('company_name'));
        $this->assertEquals('Asia/Tokyo', CompanySetting::getServerTimezone());
    }

    public function test_occurrences_are_logged_in_terms_of_application_default_timezone(): void
    {
        CompanySetting::set('server_timezone', 'America/New_York');
        date_default_timezone_set('America/New_York');
        config(['app.timezone' => 'America/New_York']);

        $this->assertEquals('America/New_York', date_default_timezone_get());
        $this->assertEquals('America/New_York', config('app.timezone'));
        $this->assertEquals('America/New_York', now()->getTimezone()->getName());
        $this->assertEquals('America/New_York', Carbon::now()->getTimezone()->getName());

        $user = User::factory()->create([
            'role'     => 'Employee',
            'status'   => 'Active',
            'timezone' => null,
        ]);
        $this->assertEquals('America/New_York', $user->getTimezone());

        $audit = AuditLog::record('Test occurrence logging');
        $this->assertEquals('America/New_York', $audit->created_at->getTimezone()->getName());
    }

    public function test_super_admin_is_hidden_from_frontend_employee_directories(): void
    {
        $superAdmin = User::factory()->create([
            'name'   => 'Secret Super Admin',
            'email'  => 'superadmin@example.com',
            'role'   => 'Super Admin',
            'status' => 'Active',
        ]);

        $employee = User::factory()->create([
            'name'   => 'Public Employee John',
            'email'  => 'john@example.com',
            'role'   => 'Employee',
            'status' => 'Active',
        ]);

        $admin = User::factory()->create([
            'role'   => 'Administrator',
            'status' => 'Active',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.employees'));
        $response->assertStatus(200);
        $response->assertSee('Public Employee John');
        $response->assertDontSee('Secret Super Admin');
    }

    public function test_super_admin_has_unrestricted_master_bypass_permissions(): void
    {
        $superAdmin = User::factory()->create([
            'role'        => 'Super Admin',
            'permissions' => [], // empty
        ]);

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertTrue($superAdmin->isAdmin());
        $this->assertTrue($superAdmin->canAccessAdmin());
        $this->assertTrue($superAdmin->hasModuleAccess('invoices', 'write'));
        $this->assertTrue($superAdmin->hasModuleAccess('executive_dashboard', 'read'));
        $this->assertTrue($superAdmin->canViewReports());
    }

    public function test_strong_rdbms_marks_records_inactive_when_child_records_exist(): void
    {
        $admin = User::factory()->create(['role' => 'Administrator', 'status' => 'Active']);
        $this->actingAs($admin);

        $employee = User::factory()->create(['role' => 'Employee', 'status' => 'Active']);
        $task = Task::create(['name' => 'Field Inspection', 'status' => 'ongoing']);

        $assignment = \App\Models\Assignment::create([
            'name'     => 'Office Renovation',
            'location' => 'HQ',
            'status'   => 'active',
        ]);

        // Create child timelog
        TimeLog::create([
            'employee_id'   => $employee->id,
            'assignment_id' => $assignment->id,
            'task_id'       => $task->id,
            'clock_in'      => now()->subHours(4),
            'clock_out'     => now(),
            'status'        => 'approved',
        ]);

        // 1. Delete Employee -> Should NOT hard delete, marked Inactive
        Livewire::test(Employees::class)
            ->call('delete', $employee->id);

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'status' => 'Inactive',
        ]);

        // 2. Delete Task -> Should NOT hard delete, marked inactive
        Livewire::test(Tasks::class)
            ->call('delete', $task->id);

        $this->assertDatabaseHas('tbl_tasks', [
            'id' => $task->id,
            'status' => 'inactive',
        ]);

        // 3. Delete Role with assigned users -> Should mark Inactive
        $customRole = Role::create([
            'name' => 'Auditor',
            'slug' => 'auditor',
            'is_system' => false,
            'status' => 'active',
        ]);
        User::factory()->create(['role' => 'Auditor']);

        Livewire::test(AccessManagement::class)
            ->call('deleteRole', $customRole->id);

        $this->assertDatabaseHas('roles', [
            'id' => $customRole->id,
            'status' => 'inactive',
        ]);

        // 4. Delete Job/Assignment with child TimeLogs -> Should mark inactive
        Livewire::test(Jobs::class)
            ->call('delete', $assignment->id);

        $this->assertDatabaseHas('tbl_assignments', [
            'id' => $assignment->id,
            'status' => 'inactive',
        ]);

        // 5. Delete Customer with associated Invoice -> Should mark active = false
        $customer = Customer::create([
            'name'   => 'Acme Corporation',
            'active' => true,
        ]);
        \App\Models\Invoice::create([
            'customer_id'    => $customer->id,
            'invoice_number' => 'INV-TEST-001',
            'status'         => 'draft',
            'total_amount'   => 500,
        ]);

        Livewire::test(Customers::class)
            ->call('delete', $customer->id);

        $this->assertDatabaseHas('customers', [
            'id'     => $customer->id,
            'active' => 0,
        ]);

        // 6. Delete Department with assigned Employee -> Should mark Inactive
        $dept = Department::create([
            'name' => 'Operations',
            'status' => 'Active',
        ]);
        $employee->update(['department_id' => $dept->id]);

        Livewire::test(Departments::class)
            ->call('delete', $dept->id);

        $this->assertDatabaseHas('tbl_departments', [
            'id' => $dept->id,
            'status' => 'Inactive',
        ]);

        // 7. Delete TimeOffPolicy with assigned employees -> Should mark active = false
        $policy = TimeOffPolicy::create([
            'name' => 'Standard Vacation',
            'policy_type' => 0,
            'accrual_method' => 0,
            'accrual_hours' => 8.00,
            'active' => true,
        ]);
        TimeOffPolicyEmployee::create([
            'policy_id' => $policy->id,
            'employee_id' => $employee->id,
            'hours_remaining' => 16.00,
        ]);

        Livewire::test(PolicyManager::class)
            ->call('delete', $policy->id);

        $this->assertDatabaseHas('time_off_policies', [
            'id' => $policy->id,
            'active' => 0,
        ]);
    }

    public function test_role_based_uam_and_custom_role_crud(): void
    {
        $admin = User::factory()->create(['role' => 'Administrator', 'status' => 'Active']);
        $this->actingAs($admin);

        // Create Custom Role
        Livewire::test(AccessManagement::class)
            ->set('roleName', 'Safety Officer')
            ->set('roleDescription', 'Monitors job safety compliance')
            ->call('saveRole')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'name' => 'Safety Officer',
            'status' => 'active',
        ]);

        $safetyRole = Role::where('name', 'Safety Officer')->first();

        // Update Role Permissions via UAM
        Livewire::test(AccessManagement::class)
            ->call('selectRole', $safetyRole->id)
            ->call('setRolePermission', 'reports', 2)
            ->call('setRolePermission', 'invoices', 0)
            ->call('saveRolePermissions')
            ->assertHasNoErrors();

        $safetyRole->refresh();
        $this->assertEquals(2, $safetyRole->permissions['reports']);
        $this->assertEquals(0, $safetyRole->permissions['invoices']);

        // Assigned user inherits permissions
        $safetyUser = User::factory()->create(['role' => 'Safety Officer']);
        $this->assertTrue($safetyUser->canViewReports());
        $this->assertFalse($safetyUser->hasModuleAccess('invoices', 'read'));
    }

    public function test_super_admin_can_add_custom_fonts(): void
    {
        $superAdmin = User::factory()->create(['role' => 'Super Admin', 'status' => 'Active']);
        $this->actingAs($superAdmin);

        Livewire::test(CompanySettings::class)
            ->set('newFontName', 'Outfit')
            ->set('newFontCss', "'Outfit', sans-serif")
            ->set('newFontGoogleUrl', 'https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&display=swap')
            ->call('addCustomFont')
            ->assertHasNoErrors();

        $available = CompanySetting::getAvailableFonts();
        $this->assertArrayHasKey('custom_outfit', $available);
        $this->assertEquals('Outfit', $available['custom_outfit']['name']);
        $this->assertTrue($available['custom_outfit']['is_custom']);
    }

    public function test_quick_search_is_strictly_permission_scoped(): void
    {
        // 1. Employee without access to reports, invoices, or settings
        $employee = User::factory()->create([
            'role'        => 'Employee',
            'permissions' => [
                'reports'          => 0,
                'invoices'         => 0,
                'company_settings' => 0,
            ],
            'status'      => 'Active',
        ]);

        $this->actingAs($employee);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('My Time Clock');
        // Hidden from quick search items for this user
        $response->assertDontSee('Company System Settings');
        $response->assertDontSee('Invoices');

        // 2. Admin with access sees administration items
        $admin = User::factory()->create(['role' => 'Administrator', 'status' => 'Active']);
        $this->actingAs($admin);

        $responseAdmin = $this->get('/dashboard');
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Company System Settings');
        $responseAdmin->assertSee('User Access Management');
    }

    public function test_navbar_renders_white_analog_clock(): void
    {
        $user = User::factory()->create(['status' => 'Active', 'timezone' => 'UTC']);
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Analog Clock (White)');
        $response->assertSee('UTC');
    }
}
