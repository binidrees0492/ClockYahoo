<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\Schedule;
use App\Models\TimeLog;
use App\Models\Location;
use App\Services\TimeLogComplianceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Locations;
use App\Livewire\MapLocations;
use App\Livewire\ScheduleBoard;

class AutoClockOutAndLocationPresetsMapTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'     => 'super_admin',
        ]);

        $this->employee = User::factory()->create([
            'role' => 'employee',
        ]);
    }

    public function test_auto_clock_out_before_commencement_of_new_day_shift_marks_unusual()
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 08:30:00', 'UTC'));

        $job = Assignment::create(['name' => 'HQ Renovation', 'status' => 'active']);
        $task = Task::create(['name' => 'Painting', 'assignment_id' => $job->id, 'status' => 'active']);

        // Open time log from earlier or yesterday
        $openLog = TimeLog::create([
            'employee_id'   => $this->employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'clock_in'      => Carbon::parse('2026-11-22 20:00:00', 'UTC'),
            'clock_out'     => null,
            'status'        => 'running',
            'is_unusual'    => false,
        ]);

        // A new shift commencing today at 08:00 UTC
        Schedule::create([
            'employee_id'    => $this->employee->id,
            'assignment_id'  => $job->id,
            'task_id'        => $task->id,
            'shift_date'     => '2026-11-23',
            'start_time'     => '08:00:00',
            'end_time'       => '16:00:00',
            'standard_hours' => 8.00,
            'status'         => 'published',
            'is_published'   => true,
        ]);

        // Enforce auto clock out
        $result = TimeLogComplianceService::enforceAutoClockOut($this->employee);

        $this->assertEquals(1, $result['auto_clocked_out_count']);

        $openLog->refresh();
        $this->assertNotNull($openLog->clock_out);
        $this->assertEquals('completed', $openLog->status);
        $this->assertTrue((bool)$openLog->is_unusual);
        $this->assertStringContainsString('Auto clocked out before commencement of the new day shift', $openLog->unusual_reason);
        $this->assertStringContainsString('auto clocked out', strtolower($openLog->notes));

        Carbon::setTestNow();
    }

    public function test_auto_clock_out_by_end_of_assigned_shift_when_not_allowed_beyond_hours()
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 17:30:00', 'UTC'));

        // Employee restricted: not allowed to track beyond assigned hours
        $this->employee->update(['permissions' => ['restrict_beyond_shift' => true]]);

        $job = Assignment::create(['name' => 'Job Sites Inc', 'status' => 'active']);
        $task = Task::create(['name' => 'Electrical Work', 'assignment_id' => $job->id, 'status' => 'active']);

        // Shift was 08:00 - 17:00
        Schedule::create([
            'employee_id'    => $this->employee->id,
            'assignment_id'  => $job->id,
            'task_id'        => $task->id,
            'shift_date'     => '2026-11-23',
            'start_time'     => '08:00:00',
            'end_time'       => '17:00:00',
            'standard_hours' => 8.00,
            'status'         => 'published',
            'is_published'   => true,
        ]);

        $openLog = TimeLog::create([
            'employee_id'   => $this->employee->id,
            'assignment_id' => $job->id,
            'task_id'       => $task->id,
            'clock_in'      => Carbon::parse('2026-11-23 08:05:00', 'UTC'),
            'clock_out'     => null,
            'status'        => 'running',
            'is_unusual'    => false,
        ]);

        $result = TimeLogComplianceService::enforceAutoClockOut($this->employee);

        $this->assertEquals(1, $result['auto_clocked_out_count']);

        $openLog->refresh();
        $this->assertNotNull($openLog->clock_out);
        $this->assertEquals('completed', $openLog->status);
        $this->assertStringContainsString('auto clocked out', strtolower($openLog->notes));
        // Clock out time capped at shift end time
        $this->assertEquals('2026-11-23 17:00:00', $openLog->clock_out->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_artisan_command_auto_clock_out_executes_successfully()
    {
        $this->artisan('timeclock:auto-clockout')
            ->expectsOutputToContain('automatic clock-out compliance check')
            ->assertExitCode(0);
    }

    public function test_location_preset_requires_mandatory_gps_coordinates_in_locations_livewire()
    {
        $this->actingAs($this->admin);

        // Attempt saving without coordinates
        Livewire::test(Locations::class)
            ->call('create')
            ->set('name', 'Mandatory Site')
            ->set('latitude', null)
            ->set('longitude', null)
            ->call('save')
            ->assertHasErrors(['latitude', 'longitude']);

        // Saving with coordinates succeeds
        Livewire::test(Locations::class)
            ->call('create')
            ->set('name', 'Downtown Hub')
            ->set('site_alias', 'DTH')
            ->set('latitude', 37.7749)
            ->set('longitude', -122.4194)
            ->set('geofence_radius', 300)
            ->set('tracking_policy', 'mandatory')
            ->set('address', '100 Market St, San Francisco, CA')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('tbl_locations', [
            'name'            => 'Downtown Hub',
            'site_alias'      => 'DTH',
            'geofence_radius' => 300,
        ]);
    }

    public function test_map_locations_component_validates_mandatory_coordinates()
    {
        $this->actingAs($this->admin);

        Livewire::test(MapLocations::class)
            ->set('name', 'Warehouse A')
            ->set('latitude', null)
            ->set('longitude', null)
            ->call('savePreset')
            ->assertHasErrors(['latitude', 'longitude']);

        Livewire::test(MapLocations::class)
            ->set('name', 'Warehouse Alpha')
            ->set('site_alias', 'W-A')
            ->set('latitude', 40.7128)
            ->set('longitude', -74.0060)
            ->set('geofence_radius', 250)
            ->set('tracking_policy', 'required')
            ->call('savePreset')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tbl_locations', [
            'name'       => 'Warehouse Alpha',
            'site_alias' => 'W-A',
        ]);
    }

    public function test_locations_view_renders_coordinates_and_map_components()
    {
        $this->actingAs($this->admin);

        $loc = Location::create([
            'name'            => 'North Station',
            'site_alias'      => 'NS',
            'latitude'        => 42.3662,
            'longitude'       => -71.0621,
            'geofence_radius' => 150,
            'tracking_policy' => 'required',
            'status'          => 'Active',
        ]);

        $response = $this->get(route('admin.locations'));
        $response->assertStatus(200);
        $response->assertSee('Locations &amp; Site Presets', false);
        $response->assertSee('North Station');
        $response->assertSee('42.36620');
        $response->assertSee('150 meters');
    }

    public function test_navigation_bar_uses_clockshark_dominating_navy_color()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('bg-[#005088]');
        $response->assertSee('border-[#f26522]');
    }
}
