<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Assignment;
use App\Models\Task;
use App\Livewire\Employees;
use App\Livewire\ScheduleBoard;
use App\Livewire\Timesheets;
use Livewire\Livewire;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MobileViewAndProfileRestrictionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_add_buttons_in_schedule_board_lead_to_respective_modules()
    {
        $admin = User::factory()->create(['role' => 'Administrator']);
        $this->actingAs($admin);

        Livewire::test(ScheduleBoard::class)
            ->call('openAddJob')
            ->assertRedirect(route('work.jobs'));

        Livewire::test(ScheduleBoard::class)
            ->call('openAddTask')
            ->assertRedirect(route('work.tasks'));

        Livewire::test(ScheduleBoard::class)
            ->call('openAddEmployee')
            ->assertRedirect(route('admin.employees'));
    }

    public function test_timesheets_and_schedule_board_render_job_parenthesis_task_unique_grouping()
    {
        $employee = User::factory()->create(['name' => 'Sara Connor', 'role' => 'Employee']);
        $this->actingAs($employee);

        $job = Assignment::create(['name' => 'Metro Terminal', 'status' => 'active']);
        $task = Task::create(['name' => 'Electrical Wiring', 'status' => 'active', 'hourly_rate' => 40.00]);

        $startOfWeek = now()->startOfWeek(\App\Models\CompanySetting::getWeekStartDay());

        \App\Models\TimeLog::create([
            'employee_id'      => $employee->id,
            'assignment_id'    => $job->id,
            'task_id'          => $task->id,
            'clock_in'         => $startOfWeek->copy()->addDays(1)->setTime(8, 0),
            'clock_out'        => $startOfWeek->copy()->addDays(1)->setTime(16, 0),
            'duration_minutes' => 480,
            'hourly_rate'      => 40.00,
            'status'           => 'pending',
        ]);

        \App\Models\Schedule::create([
            'employee_id'    => $employee->id,
            'assignment_id'  => $job->id,
            'task_id'        => $task->id,
            'shift_date'     => $startOfWeek->copy()->addDays(1)->toDateString(),
            'start_time'     => '08:00',
            'end_time'       => '16:00',
            'standard_hours' => 8.00,
            'status'         => 'published',
            'is_published'   => true,
        ]);

        // 1. Timesheets renders Metro Terminal (Electrical Wiring)
        Livewire::test(Timesheets::class)
            ->assertSee('Metro Terminal')
            ->assertSee('Electrical Wiring');

        // 2. ScheduleBoard renders Metro Terminal (Electrical Wiring)
        $admin = User::factory()->create(['role' => 'Administrator']);
        $this->actingAs($admin);

        Livewire::test(ScheduleBoard::class)
            ->assertSee('Metro Terminal')
            ->assertSee('(Electrical Wiring)');
    }

    public function test_admin_can_upload_employee_dp_in_employees_module()
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'Administrator']);
        $this->actingAs($admin);

        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        Livewire::test(Employees::class)
            ->call('openAddModal')
            ->set('first_name', 'Alex')
            ->set('last_name', 'Smith')
            ->set('email', 'alex.smith@example.com')
            ->set('role', 'Employee')
            ->set('status', 'Active')
            ->set('avatarFile', $file)
            ->call('save');

        $user = User::where('email', 'alex.smith@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->avatar);
        $this->assertStringContainsString('storage/avatars/', $user->avatar_url);
        $this->assertEquals('AS', $user->initials);

        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_employees_cannot_delete_profile_or_change_email_or_role()
    {
        Storage::fake('public');

        $employee = User::factory()->create([
            'name'  => 'Standard Worker',
            'email' => 'worker@company.com',
            'role'  => 'Employee',
        ]);
        $this->actingAs($employee);

        // 1. Employee cannot delete their own profile
        $response = $this->delete('/profile', ['password' => 'password']);
        $response->assertStatus(403);
        $this->assertNotNull($employee->fresh());

        // 2. Employee cannot edit email or role, but can update name and DP
        $file = UploadedFile::fake()->image('profile.png', 150, 150);

        $patchResponse = $this->patch('/profile', [
            'name'   => 'Worker Updated',
            'email'  => 'hacked@external.com', // Should be ignored/rejected for employees
            'role'   => 'Administrator',       // Should be ignored
            'avatar' => $file,
        ]);

        $patchResponse->assertSessionHasNoErrors();
        $employee->refresh();

        $this->assertEquals('Worker Updated', $employee->name);
        $this->assertEquals('worker@company.com', $employee->email); // Email untouched!
        $this->assertEquals('Employee', $employee->role);            // Role untouched!
        $this->assertNotNull($employee->avatar);
        Storage::disk('public')->assertExists($employee->avatar);
    }
}
