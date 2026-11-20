<?php

namespace Tests\Feature;

use App\Livewire\CompanySettings;
use App\Livewire\NavbarClock;
use App\Livewire\Profile\PersonalConfigs;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NavbarClockAndTimezoneModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_navbar_renders_right_most_clock_component(): void
    {
        $user = User::factory()->create([
            'role'     => 'Employee',
            'status'   => 'Active',
            'timezone' => 'America/New_York',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSeeLivewire(NavbarClock::class);

        $dt = new \DateTime('now', new \DateTimeZone('America/New_York'));
        $expected = $dt->format('g:i A') . ' (' . $dt->format('T') . ')';
        $response->assertSee($expected);
    }

    public function test_navbar_clock_opens_modal_and_filters_timezones(): void
    {
        $user = User::factory()->create([
            'role'     => 'Employee',
            'status'   => 'Active',
            'timezone' => 'America/New_York',
        ]);

        $this->actingAs($user);

        Livewire::test(NavbarClock::class)
            ->assertSet('showModal', false)
            ->call('openModal')
            ->assertSet('showModal', true)
            ->assertSet('selectedTimezone', 'America/New_York')
            ->set('searchTimezone', 'Tokyo')
            ->assertSee('Asia/Tokyo')
            ->call('closeModal')
            ->assertSet('showModal', false);
    }

    public function test_navbar_clock_applies_temporary_session_timezone(): void
    {
        $user = User::factory()->create([
            'role'     => 'Employee',
            'status'   => 'Active',
            'timezone' => 'America/New_York',
        ]);

        $this->actingAs($user);

        Livewire::test(NavbarClock::class)
            ->call('openModal')
            ->set('changeType', 'temp')
            ->set('selectedTimezone', 'Asia/Tokyo')
            ->call('applyTimezone')
            ->assertSet('showModal', false)
            ->assertSet('isTemp', true);

        // Verify session has temporary timezone
        $this->assertEquals('Asia/Tokyo', session('temp_timezone'));

        // Verify database profile is unchanged
        $this->assertEquals('America/New_York', $user->fresh()->timezone);

        // Verify User model resolves session temporary timezone
        $this->assertEquals('Asia/Tokyo', $user->fresh()->getTimezone());
        $this->assertTrue($user->fresh()->isTemporaryTimezone());
    }

    public function test_navbar_clock_reverts_temporary_timezone_to_profile(): void
    {
        $user = User::factory()->create([
            'role'     => 'Employee',
            'status'   => 'Active',
            'timezone' => 'America/Chicago',
        ]);

        $this->actingAs($user);
        session(['temp_timezone' => 'Europe/London']);

        $this->assertTrue($user->fresh()->isTemporaryTimezone());

        Livewire::test(NavbarClock::class)
            ->call('openModal')
            ->call('revertToProfile')
            ->assertSet('showModal', false)
            ->assertSet('isTemp', false);

        // Verify session temp timezone is cleared
        $this->assertFalse(session()->has('temp_timezone'));

        // Verify user resolves back to permanent profile timezone
        $this->assertEquals('America/Chicago', $user->fresh()->getTimezone());
        $this->assertFalse($user->fresh()->isTemporaryTimezone());
    }

    public function test_navbar_clock_applies_permanent_profile_timezone(): void
    {
        $user = User::factory()->create([
            'role'     => 'Employee',
            'status'   => 'Active',
            'timezone' => 'America/New_York',
        ]);

        $this->actingAs($user);
        session(['temp_timezone' => 'Australia/Sydney']); // had temp previously

        Livewire::test(NavbarClock::class)
            ->call('openModal')
            ->set('changeType', 'permanent')
            ->set('selectedTimezone', 'Europe/Paris')
            ->call('applyTimezone')
            ->assertSet('showModal', false)
            ->assertSet('isTemp', false);

        // Temp session cleared
        $this->assertFalse(session()->has('temp_timezone'));

        // Profile database updated permanently
        $this->assertEquals('Europe/Paris', $user->fresh()->timezone);
        $this->assertEquals('Europe/Paris', $user->fresh()->getTimezone());
        $this->assertFalse($user->fresh()->isTemporaryTimezone());
    }

    public function test_personal_configs_saves_expanded_curated_fonts(): void
    {
        $user = User::factory()->create([
            'role'            => 'Employee',
            'status'          => 'Active',
            'font_preference' => 'aptos',
        ]);

        $this->actingAs($user);

        $curatedFonts = [
            'roboto',
            'open-sans',
            'poppins',
            'montserrat',
            'plus-jakarta-sans',
            'segoe-ui',
            'fira-sans',
            'inter',
            'system',
        ];

        foreach ($curatedFonts as $font) {
            Livewire::test(PersonalConfigs::class)
                ->set('font_preference', $font)
                ->call('saveTheme')
                ->assertHasNoErrors();

            $this->assertEquals($font, $user->fresh()->font_preference);
        }
    }

    public function test_company_settings_saves_expanded_curated_fonts(): void
    {
        $admin = User::factory()->create([
            'role'   => 'Administrator',
            'status' => 'Active',
        ]);

        $this->actingAs($admin);

        Livewire::test(CompanySettings::class)
            ->set('companyFont', 'plus-jakarta-sans')
            ->set('companyTheme', 'sage dark green')
            ->call('saveBranding')
            ->assertHasNoErrors();

        $this->assertEquals('plus-jakarta-sans', CompanySetting::get('company_font'));
        $this->assertEquals('sage dark green', CompanySetting::get('company_theme'));
    }
}
