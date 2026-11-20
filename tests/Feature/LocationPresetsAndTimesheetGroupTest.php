<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Location;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\TimeLog;
use App\Livewire\MapLocations;
use App\Livewire\ScheduleBoard;
use App\Livewire\ScheduleCalendar;
use App\Livewire\Work\Tasks;
use App\Livewire\Timesheets;
use Livewire\Livewire;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LocationPresetsAndTimesheetGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_map_and_manage_location_presets()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $this->actingAs($admin);

        $response = $this->get('/map');
        $response->assertStatus(200);
        $response->assertSee('Map & Location Presets', false);

        // Test Livewire component creation
        Livewire::test(MapLocations::class)
            ->set('site_alias', 'HQ-01')
            ->set('name', 'Main Headquarters')
            ->set('address', '100 Silicon Way')
            ->set('latitude', 37.7749)
            ->set('longitude', -122.4194)
            ->set('geofence_radius', 150)
            ->set('tracking_policy', 'required')
            ->call('savePreset')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tbl_locations', [
            'site_alias'      => 'HQ-01',
            'name'            => 'Main Headquarters',
            'latitude'        => 37.7749,
            'longitude'       => -122.4194,
            'geofence_radius' => 150,
            'tracking_policy' => 'required',
        ]);

        $loc = Location::where('site_alias', 'HQ-01')->first();
        $this->assertEquals('HQ-01 - Main Headquarters (Tracked)', $loc->display_name);

        // Test editing preset
        Livewire::test(MapLocations::class)
            ->call('editPreset', $loc->id)
            ->assertSet('selectedPresetId', $loc->id)
            ->assertSet('site_alias', 'HQ-01')
            ->set('tracking_policy', 'disabled')
            ->set('geofence_radius', 300)
            ->call('savePreset')
            ->assertHasNoErrors();

        $loc->refresh();
        $this->assertEquals(300, $loc->geofence_radius);
        $this->assertEquals('disabled', $loc->tracking_policy);
        $this->assertEquals('HQ-01 - Main Headquarters (Untracked)', $loc->display_name);

        // Test deleting preset
        Livewire::test(MapLocations::class)
            ->call('deletePreset', $loc->id);

        $this->assertDatabaseMissing('tbl_locations', ['id' => $loc->id]);
    }

    public function test_schedule_board_and_calendar_and_tasks_lock_coordinates_when_preset_selected()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $this->actingAs($admin);

        $preset = Location::create([
            'site_alias'      => 'SITE-B',
            'name'            => 'Warehouse Alpha',
            'address'         => '42 Industrial Blvd',
            'latitude'        => 40.7128,
            'longitude'       => -74.0060,
            'geofence_radius' => 200,
            'tracking_policy' => 'required',
        ]);

        // 1. ScheduleBoard
        Livewire::test(ScheduleBoard::class)
            ->set('showAddShift', true)
            ->set('shiftLocationPresetId', $preset->id)
            ->assertSet('shiftLatitude', 40.7128)
            ->assertSet('shiftLongitude', -74.0060)
            ->assertSet('shiftGeofenceRadius', 200)
            ->assertSet('shiftLocationName', 'SITE-B')
            ->assertSee('Preset Locked', false)
            ->assertSee('locked to preset', false);

        // 2. ScheduleCalendar
        Livewire::test(ScheduleCalendar::class)
            ->set('showShiftModal', true)
            ->set('locationPresetId', $preset->id)
            ->assertSet('latitude', 40.7128)
            ->assertSet('longitude', -74.0060)
            ->assertSet('geofenceRadius', 200)
            ->assertSee('Preset Locked', false)
            ->assertSee('locked to preset', false);

        // 3. Tasks
        Livewire::test(Tasks::class)
            ->set('isModalOpen', true)
            ->set('locationPresetId', $preset->id)
            ->assertSet('latitude', 40.7128)
            ->assertSet('longitude', -74.0060)
            ->assertSet('geofenceRadius', 200)
            ->assertSee('Preset Locked', false)
            ->assertSee('locked to preset', false);
    }

    public function test_timesheets_groups_entries_by_job_and_task_with_leftmost_column()
    {
        $employee = User::factory()->create(['name' => 'John Doe', 'role' => 'Employee']);
        $this->actingAs($employee);

        $job = Assignment::create(['name' => 'Metro Rail Project', 'status' => 'active']);
        $task1 = Task::create(['name' => 'Track Inspection', 'status' => 'active', 'hourly_rate' => 35.00]);
        $task2 = Task::create(['name' => 'Signal Testing', 'status' => 'active', 'hourly_rate' => 45.00]);

        // Create time logs for this week
        $startOfWeek = Carbon::now()->startOfWeek(\App\Models\CompanySetting::getWeekStartDay());
        $clockIn1  = $startOfWeek->copy()->addDays(1)->setTime(9, 0); // Mon 9am
        $clockOut1 = $startOfWeek->copy()->addDays(1)->setTime(17, 0); // Mon 5pm

        $clockIn2  = $startOfWeek->copy()->addDays(2)->setTime(10, 0); // Tue 10am
        $clockOut2 = $startOfWeek->copy()->addDays(2)->setTime(14, 0); // Tue 2pm

        TimeLog::create([
            'employee_id'      => $employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task1->id,
            'clock_in'         => $clockIn1,
            'clock_out'        => $clockOut1,
            'duration_minutes' => 480,
            'hourly_rate'      => 35.00,
            'status'           => 'pending',
            'approved'         => false,
        ]);

        TimeLog::create([
            'employee_id'      => $employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task2->id,
            'clock_in'         => $clockIn2,
            'clock_out'        => $clockOut2,
            'duration_minutes' => 240,
            'hourly_rate'      => 45.00,
            'status'           => 'approved',
            'approved'         => true,
        ]);

        $component = Livewire::test(Timesheets::class);

        // Header must contain Job / Task column
        $component->assertSee('Job / Task', false);
        $component->assertSee('Metro Rail Project');
        $component->assertSee('Track Inspection');
        $component->assertSee('Signal Testing');
        $component->assertSee('8.0h');
        $component->assertSee('4.0h');

        // Check grouped rows structure
        $groupedRows = $component->viewData('groupedRows');
        $this->assertCount(2, $groupedRows);
    }
}
