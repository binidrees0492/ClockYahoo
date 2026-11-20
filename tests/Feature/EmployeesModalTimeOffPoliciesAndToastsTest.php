<?php

namespace Tests\Feature;

use App\Livewire\Employees;
use App\Livewire\TimeClock;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\TimeOffPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeesModalTimeOffPoliciesAndToastsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic company time off policies (policy_type: 0=PTO, 1=Sick, 2=Unpaid)
        TimeOffPolicy::create([
            'name'           => 'Paid Time Off',
            'policy_type'    => 0,
            'accrual_method' => 1,
            'accrual_hours'  => 80.00,
            'active'         => true,
        ]);

        TimeOffPolicy::create([
            'name'           => 'Sick Leave',
            'policy_type'    => 1,
            'accrual_method' => 1,
            'accrual_hours'  => 40.00,
            'active'         => true,
        ]);
    }

    public function test_employee_modal_lists_all_active_time_off_policies()
    {
        $admin = User::factory()->create([
            'role'   => 'Administrator',
            'status' => 'Active',
        ]);

        $this->actingAs($admin);

        Livewire::test(Employees::class)
            ->call('openAddModal')
            ->assertSet('isModalOpen', true)
            ->call('setTab', 'timeoff')
            ->assertSee('Paid Time Off')
            ->assertSee('Sick Leave')
            ->assertDontSee('[cite:')
            ->assertDontSee('[cite: 2]');
    }

    public function test_can_enroll_employee_in_policies_and_save()
    {
        $admin = User::factory()->create([
            'role'   => 'Administrator',
            'status' => 'Active',
        ]);

        $this->actingAs($admin);

        $pto = TimeOffPolicy::where('policy_type', 0)->first();
        $sick = TimeOffPolicy::where('policy_type', 1)->first();

        $comp = Livewire::test(Employees::class)
            ->call('openAddModal')
            ->set('first_name', 'Alice')
            ->set('last_name', 'Tester')
            ->set('email', 'alice.tester@example.com')
            ->set('role', 'Employee');

        // Toggle first policy (PTO) to enrolled
        $comp->call('togglePolicyEnrollment', 0);
        $policies = $comp->get('employeePolicies');
        $this->assertTrue($policies[0]['enrolled']);

        // Set hours remaining
        $policies[0]['hours_remaining'] = 45.5;
        $comp->set('employeePolicies', $policies);

        $comp->call('save')
            ->assertDispatched('toast', function ($event, $params) {
                return str_contains($params['message'], 'Employee created successfully');
            });

        $user = User::where('email', 'alice.tester@example.com')->first();
        $this->assertNotNull($user);
        $user->load('assignedPolicies');
        $this->assertCount(1, $user->assignedPolicies);
        $this->assertEquals($pto->id, $user->assignedPolicies->first()->id);
        $this->assertEquals(45.5, (float)$user->assignedPolicies->first()->pivot->hours_remaining);

        // Now test openEditModal on this user: both policies should be listed, PTO enrolled and Sick not enrolled
        Livewire::test(Employees::class)
            ->call('openEditModal', $user->id)
            ->assertSet('isModalOpen', true)
            ->call('setTab', 'timeoff')
            ->assertSee('Paid Time Off')
            ->assertSee('Sick Leave')
            ->assertSee('✓ Enrolled')
            ->assertSee('Not Enrolled')
            ->assertDontSee('[cite:');
    }

    public function test_time_clock_actions_dispatch_auto_dismissing_toasts()
    {
        $job = Assignment::create([
            'name'   => 'Main Site',
            'status' => 'active',
        ]);

        $task = Task::create([
            'name'          => 'Assembly',
            'assignment_id' => $job->id,
            'status'        => 'active',
        ]);

        $user = User::factory()->create([
            'role'        => 'Employee',
            'status'      => 'Active',
            'permissions' => [
                'allow_web_clock' => true,
                'assigned_jobs'   => [$job->id],
                'assigned_tasks'  => [$task->id],
            ],
        ]);

        $this->actingAs($user);

        // Clock In
        $clock = Livewire::test(TimeClock::class)
            ->set('selectedAssignment', $job->id)
            ->set('selectedTask', $task->id)
            ->call('clockIn')
            ->assertDispatched('toast', function ($event, $params) {
                return str_contains($params['message'], 'Clocked in');
            });

        $log = TimeLog::where('employee_id', $user->id)->whereNull('clock_out')->first();
        $this->assertNotNull($log);

        // Start Break
        $clock->call('startBreak')
            ->assertDispatched('toast', function ($event, $params) {
                return str_contains($params['message'], 'Break started');
            });

        // End Break
        $clock->call('endBreak')
            ->assertDispatched('toast', function ($event, $params) {
                return str_contains($params['message'], 'Break ended');
            });

        // Attempt immediate Clock Out (blocked by 1-minute guard)
        $clock->call('clockOut')
            ->assertDispatched('toast', function ($event, $params) {
                return str_contains($params['message'], 'Please wait at least 1 minute');
            });

        // Fast-forward past 1-minute guard
        \Carbon\Carbon::setTestNow(now()->addSeconds(65));

        // Clock Out succeeds
        $clock->call('clockOut')
            ->assertDispatched('toast', function ($event, $params) {
                return str_contains($params['message'], 'Clocked out');
            });

        \Carbon\Carbon::setTestNow();
    }
}
