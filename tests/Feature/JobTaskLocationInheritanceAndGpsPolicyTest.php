<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\Location;
use App\Models\CompanySetting;
use App\Livewire\Work\Jobs as JobsComponent;
use App\Livewire\Work\Tasks as TasksComponent;
use Livewire\Livewire;

class JobTaskLocationInheritanceAndGpsPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $locationHQ;
    protected $locationRemote;

    protected function setUp(): void
    {
        parent::setUp();
        CompanySetting::set('company_name', 'ClockYahoo Inc');

        $this->admin = User::factory()->create([
            'role' => 'Administrator',
            'permissions' => ['all' => true],
        ]);

        $this->locationHQ = Location::create([
            'name'            => 'Headquarters Campus',
            'site_alias'      => 'HQ',
            'latitude'        => 37.7749295,
            'longitude'       => -122.4194155,
            'geofence_radius' => 250,
            'tracking_policy' => 'required',
            'status'          => 'active',
        ]);

        $this->locationRemote = Location::create([
            'name'            => 'Remote Field Site B',
            'site_alias'      => 'RFS-B',
            'latitude'        => 34.0522342,
            'longitude'       => -118.2436849,
            'geofence_radius' => 500,
            'tracking_policy' => 'optional',
            'status'          => 'active',
        ]);
    }

    /**
     * Requirement: Tasks cannot be created without a parent job (assignment_id is strictly required)
     */
    public function test_tasks_cannot_be_created_without_parent_job()
    {
        $this->actingAs($this->admin);

        Livewire::test(TasksComponent::class)
            ->call('openModal')
            ->set('name', 'Orphan Task Attempt')
            ->set('assignmentId', null)
            ->call('save')
            ->assertHasErrors(['assignmentId' => 'required']);

        $this->assertDatabaseMissing('tbl_tasks', [
            'name' => 'Orphan Task Attempt',
        ]);
    }

    /**
     * Requirement: Job creation supports selecting a location from location presets dropdown
     * and sets coordinates, radius, and suggested GPS policy
     */
    public function test_job_creation_with_location_preset_dropdown()
    {
        $this->actingAs($this->admin);

        $jobComponent = Livewire::test(JobsComponent::class)
            ->call('openModal')
            ->set('name', 'Campus Renovation Phase 1')
            ->set('locationId', $this->locationHQ->id)
            ->assertSet('latitude', 37.7749295)
            ->assertSet('longitude', -122.4194155)
            ->assertSet('geofenceRadius', 250)
            ->assertSet('gpsPolicy', 'mandatory')
            ->assertDispatched('job-location-preset-selected')
            ->call('save')
            ->assertHasNoErrors();

        $job = Assignment::where('name', 'Campus Renovation Phase 1')->first();
        $this->assertNotNull($job);
        $this->assertEquals($this->locationHQ->id, $job->location_id);
        $this->assertEquals('Headquarters Campus', $job->location);
        $this->assertEquals(37.7749295, (float)$job->latitude);
        $this->assertEquals(-122.4194155, (float)$job->longitude);
        $this->assertEquals(250, $job->geofence_radius);
        $this->assertEquals('mandatory', $job->gps_policy);
    }

    /**
     * Requirement: GPS policy override warns if violating parent location policy,
     * but still successfully creates/saves the job
     */
    public function test_job_gps_policy_override_warns_and_allows_creation()
    {
        $this->actingAs($this->admin);

        // Location HQ has tracking_policy = 'required'
        // User overrides to 'none' (Off)
        $component = Livewire::test(JobsComponent::class)
            ->call('openModal')
            ->set('name', 'Flexible Hours Project')
            ->set('locationId', $this->locationHQ->id)
            ->set('gpsPolicy', 'none');

        // Check that warning is populated
        $this->assertNotNull($component->get('gpsPolicyWarning'));
        $this->assertStringContainsString('more lenient than the location\'s policy', $component->get('gpsPolicyWarning'));

        // Saving should succeed without validation errors!
        $component->call('save')
            ->assertHasNoErrors();

        $job = Assignment::where('name', 'Flexible Hours Project')->first();
        $this->assertNotNull($job);
        $this->assertEquals('none', $job->gps_policy);
    }

    /**
     * Requirement: Tasks automatically inherit location and coordinates from parent Job,
     * but allow user to override to any other location preset
     */
    public function test_task_inherits_location_from_parent_job_and_allows_override()
    {
        $this->actingAs($this->admin);

        $job = Assignment::create([
            'name'            => 'Tower Construction',
            'location_id'     => $this->locationHQ->id,
            'location'        => $this->locationHQ->name,
            'latitude'        => $this->locationHQ->latitude,
            'longitude'       => $this->locationHQ->longitude,
            'geofence_radius' => $this->locationHQ->geofence_radius,
            'gps_policy'      => 'mandatory',
            'status'          => 'active',
        ]);

        // When parent job is selected on Task, it inherits the location
        $taskComponent = Livewire::test(TasksComponent::class)
            ->call('openModal')
            ->set('name', 'Foundation Piling')
            ->set('assignmentId', $job->id)
            ->assertSet('locationPresetId', $this->locationHQ->id)
            ->assertSet('latitude', 37.7749295)
            ->assertSet('longitude', -122.4194155)
            ->assertSet('geofenceRadius', 250)
            ->assertSet('gpsPolicy', 'mandatory')
            ->assertDispatched('task-location-preset-selected');

        // Save with inherited location
        $taskComponent->call('save')
            ->assertHasNoErrors();

        $task = Task::where('name', 'Foundation Piling')->first();
        $this->assertNotNull($task);
        $this->assertEquals($job->id, $task->assignment_id);
        $this->assertEquals($this->locationHQ->id, $task->location_id);
        $this->assertEquals(37.7749295, (float)$task->latitude);

        // Now test override: Change location to Remote Field Site B
        $editComponent = Livewire::test(TasksComponent::class)
            ->call('edit', $task->id)
            ->set('locationPresetId', $this->locationRemote->id)
            ->assertSet('latitude', 34.0522342)
            ->assertSet('longitude', -118.2436849)
            ->assertSet('geofenceRadius', 500)
            ->call('save')
            ->assertHasNoErrors();

        $task->refresh();
        $this->assertEquals($this->locationRemote->id, $task->location_id);
        $this->assertEquals(34.0522342, (float)$task->latitude);
        $this->assertEquals(-118.2436849, (float)$task->longitude);
        $this->assertEquals(500, $task->geofence_radius);
    }

    /**
     * Requirement: Adding/updating GPS policy does not trigger "invalid policy" error
     * and supports override warning without blocking task creation
     */
    public function test_task_gps_policy_override_does_not_fail_and_warns()
    {
        $this->actingAs($this->admin);

        $job = Assignment::create([
            'name'        => 'Downtown Renovation',
            'location_id' => $this->locationHQ->id,
            'status'      => 'active',
        ]);

        // Task with locationHQ (tracking_policy = 'required') overridden to 'flag'
        $component = Livewire::test(TasksComponent::class)
            ->call('openModal')
            ->set('name', 'Safety Inspection')
            ->set('assignmentId', $job->id)
            ->set('gpsPolicy', 'flag');

        // Warning should be present
        $this->assertNotNull($component->get('gpsPolicyWarning'));
        $this->assertStringContainsString('more lenient than the location\'s policy', $component->get('gpsPolicyWarning'));

        // Saving must succeed without "invalid policy" error!
        $component->call('save')
            ->assertHasNoErrors();

        $savedTask = Task::where('name', 'Safety Inspection')->first();
        $this->assertNotNull($savedTask);
        $this->assertEquals('flag', $savedTask->gps_policy);
    }
}
