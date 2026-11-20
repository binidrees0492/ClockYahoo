<?php

namespace Tests\Feature;

use App\Livewire\TimeOff\RequestManager;
use App\Models\CompanySetting;
use App\Models\TimeOffPolicy;
use App\Models\TimeOffPolicyEmployee;
use App\Models\TimeOffRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TimeOffWeeklyBreakdownTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected TimeOffPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'Company Admin',
            'status' => 'Active',
            'timezone' => 'America/New_York',
        ]);

        $this->policy = TimeOffPolicy::create([
            'name' => 'Paid Vacation',
            'type' => 'paid',
            'default_allowance_hours' => 80.0,
            'accrual_frequency' => 'yearly',
            'is_active' => true,
        ]);

        TimeOffPolicyEmployee::create([
            'employee_id' => $this->user->id,
            'policy_id' => $this->policy->id,
            'hours_remaining' => 80.0,
        ]);
    }

    public function test_dates_and_hours_breakdown_groups_by_configured_week_start(): void
    {
        // Set week start day to Monday (1)
        CompanySetting::set('week_start_day', '1');

        $this->actingAs($this->user);

        // Date range spanning 2 weeks: Monday Oct 19, 2026 to Sunday Nov 1, 2026 (14 days)
        $component = Livewire::test(RequestManager::class)
            ->call('openRequestModal')
            ->set('start_date', '2026-10-19')
            ->set('end_date', '2026-11-01')
            ->call('regenerateDaysBreakdown');

        $weeklyBreakdown = $component->get('weeklyBreakdown');

        // Should have exactly 2 weekly groups
        $this->assertCount(2, $weeklyBreakdown);

        // Week 1 should start on Oct 19 and end on Oct 25
        $this->assertEquals('2026-10-19', $weeklyBreakdown[0]['start_date']);
        $this->assertEquals('2026-10-25', $weeklyBreakdown[0]['end_date']);
        $this->assertCount(7, $weeklyBreakdown[0]['days']);

        // Week 2 should start on Oct 26 and end on Nov 01
        $this->assertEquals('2026-10-26', $weeklyBreakdown[1]['start_date']);
        $this->assertEquals('2026-11-01', $weeklyBreakdown[1]['end_date']);
        $this->assertCount(7, $weeklyBreakdown[1]['days']);
    }

    public function test_breakdown_week_pagination_navigation(): void
    {
        CompanySetting::set('week_start_day', '0'); // Sunday

        $this->actingAs($this->user);

        // Spanning 3 weeks: Oct 11, 2026 to Oct 31, 2026 (21 days)
        $component = Livewire::test(RequestManager::class)
            ->call('openRequestModal')
            ->set('start_date', '2026-10-11')
            ->set('end_date', '2026-10-31')
            ->call('regenerateDaysBreakdown');

        $component->assertSet('breakdown_week_page', 0);

        // Navigate forward
        $component->call('nextBreakdownWeek')
            ->assertSet('breakdown_week_page', 1);

        $component->call('nextBreakdownWeek')
            ->assertSet('breakdown_week_page', 2);

        // Clamped at max page
        $component->call('nextBreakdownWeek')
            ->assertSet('breakdown_week_page', 2);

        // Navigate back
        $component->call('prevBreakdownWeek')
            ->assertSet('breakdown_week_page', 1);

        // Direct page set
        $component->call('setBreakdownWeekPage', 0)
            ->assertSet('breakdown_week_page', 0);
    }

    public function test_toggle_unpaid_and_adjust_hours_updates_totals_correctly(): void
    {
        CompanySetting::set('week_start_day', '1');

        $this->actingAs($this->user);

        // Monday to Friday: 5 days, 8 hrs/day = 40h total
        $component = Livewire::test(RequestManager::class)
            ->call('openRequestModal')
            ->set('start_date', '2026-10-19')
            ->set('end_date', '2026-10-23')
            ->call('regenerateDaysBreakdown');

        $component->assertSet('total_requested_hours', 40.0)
            ->assertSet('paid_hours', 40.0)
            ->assertSet('unpaid_hours', 0.0);

        // Toggle day 0 (Oct 19) to unpaid
        $component->call('toggleDayUnpaid', 0);

        $component->assertSet('total_requested_hours', 40.0)
            ->assertSet('paid_hours', 32.0)
            ->assertSet('unpaid_hours', 8.0);

        // Adjust hours on day 1 (Oct 20) to 4 hours
        $component->set('days_breakdown.1.hours', 4.0);

        $component->assertSet('total_requested_hours', 36.0)
            ->assertSet('paid_hours', 28.0)
            ->assertSet('unpaid_hours', 8.0);
    }

    public function test_detail_breakdown_supports_weekly_pagination(): void
    {
        CompanySetting::set('week_start_day', '1');

        $this->actingAs($this->user);

        // Create a 2-week request in database
        $timeOffReq = TimeOffRequest::create([
            'employee_id' => $this->user->id,
            'policy_id' => $this->policy->id,
            'start_at' => Carbon::parse('2026-10-19')->startOfDay(),
            'end_at' => Carbon::parse('2026-11-01')->endOfDay(),
            'hours' => 32.0,
            'paid_hours' => 32.0,
            'unpaid_hours' => 0.0,
            'status' => 'pending',
            'daily_breakdown' => [
                ['date' => '2026-10-19', 'day_name' => 'Monday', 'formatted' => 'Oct 19, 2026', 'is_weekend' => false, 'type' => 'full', 'is_unpaid' => false, 'hours' => 8.0],
                ['date' => '2026-10-20', 'day_name' => 'Tuesday', 'formatted' => 'Oct 20, 2026', 'is_weekend' => false, 'type' => 'full', 'is_unpaid' => false, 'hours' => 8.0],
                ['date' => '2026-10-26', 'day_name' => 'Monday', 'formatted' => 'Oct 26, 2026', 'is_weekend' => false, 'type' => 'full', 'is_unpaid' => false, 'hours' => 8.0],
                ['date' => '2026-10-27', 'day_name' => 'Tuesday', 'formatted' => 'Oct 27, 2026', 'is_weekend' => false, 'type' => 'full', 'is_unpaid' => false, 'hours' => 8.0],
            ],
        ]);

        $component = Livewire::test(RequestManager::class)
            ->call('openViewModal', $timeOffReq->id);

        $detailWeekly = $component->get('detailWeeklyBreakdown');
        $this->assertCount(2, $detailWeekly);

        $component->assertSet('detail_breakdown_week_page', 0)
            ->call('nextDetailBreakdownWeek')
            ->assertSet('detail_breakdown_week_page', 1)
            ->call('prevDetailBreakdownWeek')
            ->assertSet('detail_breakdown_week_page', 0);
    }
}
