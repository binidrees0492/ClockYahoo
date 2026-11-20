<?php

namespace Tests\Feature;

use App\Livewire\CompanySettings;
use App\Models\CompanySetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminBaseThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_default_base_theme_is_classic(): void
    {
        $this->assertEquals('classic', CompanySetting::getBaseTheme());
        $this->assertFalse(CompanySetting::isModernTheme());
    }

    public function test_super_admin_can_switch_base_theme_to_modern(): void
    {
        $superAdmin = User::factory()->create([
            'role'   => 'Super Admin',
            'status' => 'Active',
        ]);

        $this->actingAs($superAdmin);

        Livewire::test(CompanySettings::class)
            ->call('switchBaseTheme', 'modern')
            ->assertDispatched('base-theme-switched', theme: 'modern')
            ->assertSet('toastType', 'success');

        $this->assertEquals('modern', CompanySetting::getBaseTheme());
        $this->assertTrue(CompanySetting::isModernTheme());

        // Switch back to classic
        Livewire::test(CompanySettings::class)
            ->call('switchBaseTheme', 'classic')
            ->assertDispatched('base-theme-switched', theme: 'classic')
            ->assertSet('toastType', 'success');

        $this->assertEquals('classic', CompanySetting::getBaseTheme());
        $this->assertFalse(CompanySetting::isModernTheme());
    }

    public function test_super_admin_can_save_modern_ux_enhancements(): void
    {
        $superAdmin = User::factory()->create([
            'role'   => 'Super Admin',
            'status' => 'Active',
        ]);

        $this->actingAs($superAdmin);

        Livewire::test(CompanySettings::class)
            ->set('applicationBaseTheme', 'modern')
            ->set('modernHighContrast', true)
            ->set('modernMicroAnimations', true)
            ->set('modernCustomIcons', true)
            ->call('saveBranding')
            ->assertHasNoErrors()
            ->assertSet('toastType', 'success');

        $this->assertEquals('modern', CompanySetting::getBaseTheme());
        $this->assertTrue((bool) CompanySetting::get('modern_high_contrast'));
        $this->assertTrue((bool) CompanySetting::get('modern_micro_animations'));
        $this->assertTrue((bool) CompanySetting::get('modern_custom_icons'));
    }

    public function test_modern_layout_renders_modern_classes_without_react_sync_elements(): void
    {
        $admin = User::factory()->create([
            'role'   => 'Administrator',
            'status' => 'Active',
        ]);

        CompanySetting::setBaseTheme('modern');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('base-modern modern-layout', false);
        $response->assertDontSee('id="react-top-loader"', false);
        $response->assertDontSee('id="react-realtime-sync-pill"', false);
        $response->assertDontSee('Realtime Synced', false);
        $response->assertDontSee('Modern UI', false);
        $response->assertDontSee('React Realtime SPA Engine', false);
    }

    public function test_classic_layout_preserves_clockshark_replica_layout(): void
    {
        $admin = User::factory()->create([
            'role'   => 'Administrator',
            'status' => 'Active',
        ]);

        CompanySetting::setBaseTheme('classic');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('base-classic', false);
        $response->assertDontSee('base-modern modern-layout', false);
        $response->assertDontSee('id="react-realtime-sync-pill"', false);
        $response->assertDontSee('Modern UI', false);
    }

    public function test_employee_cannot_access_company_settings(): void
    {
        $employee = User::factory()->create([
            'role'   => 'Employee',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($employee)->get(route('admin.company-settings'));
        $response->assertStatus(403);
    }
}
