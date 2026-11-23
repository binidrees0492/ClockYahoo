<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\Schedule;
use App\Models\TimeLog;
use App\Models\User;
use App\Notifications\ShiftAssignedNotification;
use App\Notifications\TimeLogSubmittedNotification;
use App\Notifications\UnusualTimeActivityNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TimeLogComplianceService
{
    /**
     * Calculate distance between two GPS coordinates using Haversine formula (meters).
     */
    public static function calculateDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // in meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo   = deg2rad($lat2);
        $lonTo   = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $earthRadius, 1);
    }

    /**
     * Check GPS location compliance against task policy / schedule.
     * Policy specified against a task: 'none' (off by default) | 'flag' | 'mandatory'
     */
    public static function checkLocation(User $employee, ?Schedule $schedule, ?float $lat, ?float $lng, ?\App\Models\Task $task = null): array
    {
        // 1. Task-level GPS tracking policy is primary; off ('none') by default!
        $policy = $task?->gps_policy ?? 'none';

        if ($policy === 'none') {
            return [
                'allowed'  => true,
                'flagged'  => false,
                'reason'   => null,
                'distance' => null,
            ];
        }

        // If coordinates were not provided / GPS unavailable:
        if ($lat === null || $lng === null) {
            return [
                'allowed'  => true, // Log time anyway, do not block!
                'flagged'  => true,
                'reason'   => 'GPS N/A - Coordinates unavailable or permission denied.',
                'distance' => null,
            ];
        }

        // Target coordinates from schedule
        $targetLat    = $schedule?->latitude;
        $targetLng    = $schedule?->longitude;
        $radiusMeters = $schedule?->geofence_radius ?: ($task?->geofence_radius ?: 200);

        if ($targetLat !== null && $targetLng !== null) {
            $dist = self::calculateDistanceMeters($lat, $lng, (float) $targetLat, (float) $targetLng);

            if ($dist > $radiusMeters) {
                $msg = sprintf(
                    'Location discrepancy: %sm away from scheduled location (authorized radius: %sm).',
                    number_format($dist, 0),
                    number_format($radiusMeters, 0)
                );

                return [
                    'allowed'  => true,
                    'flagged'  => true,
                    'reason'   => $msg,
                    'distance' => $dist,
                ];
            }
        }

        return [
            'allowed'  => true,
            'flagged'  => false,
            'reason'   => null,
            'distance' => null,
        ];
    }

    /**
     * Check if a time log has unusual activity (logged beyond/after shift hours).
     * System monitors logging according to the allocated shift hours for that task + job.
     */
    public static function evaluateUnusualActivity(TimeLog $log): array
    {
        $employee = $log->employee;
        if (!$employee || !$log->clock_in) {
            return ['is_unusual' => false, 'reason' => null];
        }

        $logDate = $log->clock_in->toDateString();

        // Find schedule for employee, matching task & job if available (ONLY PUBLISHED)
        $scheduleQuery = Schedule::where('employee_id', $employee->id)
            ->where(function ($q) {
                $q->where('status', 'published')->orWhere('is_published', true);
            })
            ->whereDate('shift_date', $logDate);

        if ($log->task_id) {
            $scheduleQuery->where(function ($q) use ($log) {
                $q->where('task_id', $log->task_id)->orWhereNull('task_id');
            });
        }
        if ($log->assignment_id) {
            $scheduleQuery->where(function ($q) use ($log) {
                $q->where('assignment_id', $log->assignment_id)->orWhereNull('assignment_id');
            });
        }

        $schedule = $scheduleQuery->orderBy('start_time')->first();

        $reasons = [];

        $jobShift = $log->assignment_id ? $employee->getJobShiftConfig((int) $log->assignment_id) : null;
        $allowBeyond = $jobShift && !empty($jobShift['allow_beyond_shift']);

        if ($jobShift) {
            $shiftHours = isset($jobShift['shift_hours']) && (float) $jobShift['shift_hours'] > 0
                ? (float) $jobShift['shift_hours']
                : 8.0;
            $jobName = $log->assignment?->name ?? 'Job';

            // If not allowed to record beyond shift hours and duration exceeds shift hours
            if (!$allowBeyond) {
                $maxShiftMinutes = (int) round($shiftHours * 60);
                if ($log->duration_minutes > $maxShiftMinutes) {
                    $overMinutes = $log->duration_minutes - $maxShiftMinutes;
                    $reasons[] = sprintf(
                        "Exceeded assigned shift of %s hrs for job '%s' (logged %s hrs by %d mins). Over-hours exception not granted.",
                        number_format($shiftHours, 1),
                        $jobName,
                        number_format($log->duration_minutes / 60, 2),
                        $overMinutes
                    );
                }
            }
        }

        if ($schedule) {
            // Check scheduled start and end
            $scheduledStart = Carbon::parse($logDate . ' ' . self::extractTimeOnly($schedule->start_time));
            $scheduledEnd   = Carbon::parse($logDate . ' ' . self::extractTimeOnly($schedule->end_time));

            // Handle overnight shifts spanning past midnight
            if ($scheduledEnd->lte($scheduledStart)) {
                $scheduledEnd->addDay();
            }

            // Clocked out significantly after shift end (> 15 minutes)
            if ($log->clock_out && $log->clock_out->gt($scheduledEnd->copy()->addMinutes(15))) {
                $diffMin = abs((int) round($scheduledEnd->diffInMinutes($log->clock_out)));
                $reasons[] = "Clocked out {$diffMin} minutes after scheduled shift end ({$scheduledEnd->format('H:i')}).";
            }

            // Clocked in significantly before shift start (> 30 minutes)
            if ($log->clock_in->lt($scheduledStart->copy()->subMinutes(30))) {
                $diffMin = abs((int) round($scheduledStart->diffInMinutes($log->clock_in)));
                $reasons[] = "Clocked in {$diffMin} minutes before scheduled shift start ({$scheduledStart->format('H:i')}).";
            }

            // Duration exceeds allocated shift hours for that task + job
            $allocatedHours = $schedule->standard_hours ?: 8.0;
            $standardShiftMinutes = $allocatedHours * 60;
            if (!$allowBeyond && $log->duration_minutes > ($standardShiftMinutes + 15)) {
                $overtimeMin = $log->duration_minutes - $standardShiftMinutes;
                $taskName = $log->task->name ?? 'Task';
                $reasons[] = sprintf("Logged %s hrs — exceeded allocated shift hours (%s hrs) for %s by %d mins.", number_format($log->duration_minutes / 60, 1), number_format($allocatedHours, 1), $taskName, $overtimeMin);
            }
        } elseif (!$jobShift) {
            // No schedule assigned for this day and no per-job shift configured: check user standard shift
            $userStart = $employee->standard_shift_start ?: '08:00';
            $userEnd   = $employee->standard_shift_end ?: '17:00';
            $userStdHours = (float) ($employee->standard_shift_hours ?: 8.00);

            $expectedStart = Carbon::parse($logDate . ' ' . $userStart);
            $expectedEnd   = Carbon::parse($logDate . ' ' . $userEnd);
            if ($expectedEnd->lte($expectedStart)) {
                $expectedEnd->addDay();
            }

            // Daytime shift check: only applies if shift began during daytime (before standard workday end)
            if ($log->clock_in->lt($expectedEnd)) {
                if ($log->clock_out && $log->clock_out->gt($expectedEnd->copy()->addMinutes(30))) {
                    $diffMin = abs((int) round($expectedEnd->diffInMinutes($log->clock_out)));
                    $reasons[] = "Logged {$diffMin} minutes past standard workday end ({$expectedEnd->format('H:i')}) without a scheduled shift.";
                }
            } else {
                // Evening or overnight shift starting at or after standard workday end (e.g., 10:20 PM)
                $expectedShiftEnd = $log->clock_in->copy()->addMinutes((int) ($userStdHours * 60));
                if ($log->clock_out && $log->clock_out->gt($expectedShiftEnd->copy()->addMinutes(45))) {
                    $diffMin = abs((int) round($expectedShiftEnd->diffInMinutes($log->clock_out)));
                    $reasons[] = "Off-hours shift extended {$diffMin} minutes past standard duration ({$userStdHours} hrs).";
                }
            }

            if (!$allowBeyond && $log->duration_minutes > ($userStdHours * 60 + 45)) {
                $reasons[] = sprintf("Logged %s hrs exceeding standard %s hr limit.", number_format($log->duration_minutes / 60, 1), $userStdHours);
            }
        }

        // Include location flag if present
        if ($log->location_flagged && $log->location_flag_reason) {
            $reasons[] = $log->location_flag_reason;
        }

        if (!empty($reasons)) {
            return [
                'is_unusual' => true,
                'reason'     => implode(' | ', $reasons),
            ];
        }

        return [
            'is_unusual' => false,
            'reason'     => null,
        ];
    }

    /**
     * Process a completed / submitted time log: check unusual activity and notify HR.
     */
    public static function processTimeLogSubmission(TimeLog $log): void
    {
        $evaluation = self::evaluateUnusualActivity($log);
        if ($evaluation['is_unusual']) {
            $log->is_unusual = true;
            $existingReason = $log->unusual_reason;
            if ($existingReason && !str_contains($existingReason, $evaluation['reason'])) {
                $log->unusual_reason = $existingReason . ' | ' . $evaluation['reason'];
            } elseif (!$existingReason) {
                $log->unusual_reason = $evaluation['reason'];
            }
            $log->saveQuietly();
        }

        $employee = $log->employee;
        if (!$employee) return;

        // Get HR Managers and Admins + direct manager
        $stakeholders = User::whereIn('role', ['Administrator', 'HR Manager'])
            ->where('id', '!=', $employee->id)
            ->get();

        if ($employee->manager_id && $employee->manager_id !== $employee->id) {
            $mgr = User::find($employee->manager_id);
            if ($mgr && !$stakeholders->contains('id', $mgr->id)) {
                $stakeholders->push($mgr);
            }
        }

        $dateFormatted = $log->clock_in ? $log->clock_in->format('M d, Y') : 'Recent';
        $hoursFormatted = number_format(($log->duration_minutes ?: 0) / 60, 2);
        $jobName = $log->assignment?->name ?? 'General Work';

        // 1. Notify HR / Stakeholders of submitted time log
        foreach ($stakeholders as $stakeholder) {
            try {
                $stakeholder->notify(new TimeLogSubmittedNotification(
                    employeeName: $employee->name,
                    date: $dateFormatted,
                    hours: $hoursFormatted,
                    jobName: $jobName,
                    timeLogId: $log->id,
                    isManual: (bool) $log->is_manual
                ));
            } catch (\Throwable $e) {
                Log::warning("Failed to notify HR of time log: " . $e->getMessage());
            }
        }

        // 2. If unusual activity detected, notify both employee and HR
        if ($log->is_unusual) {
            // Notify employee
            try {
                $employee->notify(new UnusualTimeActivityNotification(
                    employeeName: $employee->name,
                    date: $dateFormatted,
                    reason: $log->unusual_reason,
                    timeLogId: $log->id,
                    isHrView: false
                ));
            } catch (\Throwable $e) {
                Log::warning("Failed to notify employee of unusual activity: " . $e->getMessage());
            }

            // Notify HR stakeholders of unusual activity
            foreach ($stakeholders as $stakeholder) {
                try {
                    $stakeholder->notify(new UnusualTimeActivityNotification(
                        employeeName: $employee->name,
                        date: $dateFormatted,
                        reason: $log->unusual_reason,
                        timeLogId: $log->id,
                        isHrView: true
                    ));
                } catch (\Throwable $e) {
                    Log::warning("Failed to notify HR of unusual activity: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Notify all stakeholders when a schedule is assigned.
     */
    public static function notifyScheduleAssigned(Schedule $schedule, ?int $assignedById = null): void
    {
        $employee = $schedule->employee;
        if (!$employee) return;

        $jobName = $schedule->assignment?->name ?? 'Assigned Job';
        $dateFormatted = $schedule->shift_date ? $schedule->shift_date->format('M d, Y') : '';
        $timeRange = substr($schedule->start_time, 0, 5) . ' - ' . substr($schedule->end_time, 0, 5);

        // 1. Notify employee
        try {
            $employee->notify(new ShiftAssignedNotification(
                shiftDate: $dateFormatted,
                jobName: $jobName,
                timeRange: $timeRange,
                forStakeholder: false
            ));
        } catch (\Throwable $e) {
            Log::warning("Failed to notify employee of assigned shift: " . $e->getMessage());
        }

        // 2. Stakeholders: employee's manager + HR Managers / Admins
        $stakeholders = collect();

        if ($employee->manager_id && $employee->manager_id !== $employee->id && $employee->manager_id !== $assignedById) {
            $mgr = User::find($employee->manager_id);
            if ($mgr) {
                $stakeholders->push($mgr);
            }
        }

        $adminsAndHr = User::whereIn('role', ['Administrator', 'HR Manager'])
            ->where('id', '!=', $employee->id)
            ->where('id', '!=', (int) $assignedById)
            ->get();

        foreach ($adminsAndHr as $admin) {
            if (!$stakeholders->contains('id', $admin->id)) {
                $stakeholders->push($admin);
            }
        }

        foreach ($stakeholders as $stakeholder) {
            try {
                $stakeholder->notify(new ShiftAssignedNotification(
                    shiftDate: $dateFormatted,
                    jobName: "{$employee->name} — {$jobName}",
                    timeRange: $timeRange,
                    forStakeholder: true,
                    employeeName: $employee->name
                ));
            } catch (\Throwable $e) {
                Log::warning("Failed to notify stakeholder of assigned shift: " . $e->getMessage());
            }
        }
    }

    /**
     * Enforce automatic clock-out rules on open time logs:
     * 1. Rule A (New Day Shift Commencement):
     *    If an employee is already clocked in and a new day shift commences,
     *    auto clock out (with note "auto clocked out") before commencement of the new day shift,
     *    and mark as unusual activity.
     * 2. Rule B (Not allowed to track beyond assigned hours):
     *    If an employee is not allowed to track beyond assigned hours,
     *    auto clock out by the end of the assigned shift time (with note "auto clocked out").
     */
    public static function enforceAutoClockOut(?User $user = null): array
    {
        $query = TimeLog::whereNull('clock_out')
            ->where('status', '!=', 'completed')
            ->notArchived();

        if ($user) {
            $query->where('employee_id', $user->id);
        }

        $openLogs = $query->with(['employee', 'assignment', 'task'])->get();
        $processed = [];

        foreach ($openLogs as $log) {
            $employee = $log->employee;
            if (!$employee) continue;

            $empTz = $employee->getTimezone() ?: CompanySetting::getServerTimezone();
            $nowUtc = Carbon::now('UTC');
            $clockInUtc = $log->getClockInCarbon() ?: Carbon::parse($log->clock_in, 'UTC');
            $clockInLocal = $clockInUtc->copy()->setTimezone($empTz);
            $nowLocal = $nowUtc->copy()->setTimezone($empTz);

            $clockInDate = $clockInLocal->toDateString();
            $todayDate = $nowLocal->toDateString();

            $autoClockedOut = false;
            $autoClockOutTimeUtc = null;
            $reason = null;
            $isUnusual = false;

            // --- RULE B: Over-hours / Restrict tracking beyond assigned shift hours ---
            $jobShift = $log->assignment_id ? $employee->getJobShiftConfig((int) $log->assignment_id) : null;
            $allowBeyond = $jobShift && !empty($jobShift['allow_beyond_shift']);
            $restrictBeyondShift = (!$allowBeyond && $jobShift) || (isset($employee->permissions['restrict_beyond_shift']) && $employee->permissions['restrict_beyond_shift']);

            $assignedShiftEndTimeUtc = null;
            $schedule = Schedule::where('employee_id', $employee->id)
                ->published()
                ->whereDate('shift_date', $clockInDate)
                ->when($log->assignment_id, function ($q) use ($log) {
                    $q->where(function ($sub) use ($log) {
                        $sub->where('assignment_id', $log->assignment_id)->orWhereNull('assignment_id');
                    });
                })
                ->when($log->task_id, function ($q) use ($log) {
                    $q->where(function ($sub) use ($log) {
                        $sub->where('task_id', $log->task_id)->orWhereNull('task_id');
                    });
                })
                ->first();

            if ($schedule) {
                $schedStartLocal = Carbon::parse($schedule->shift_date->format('Y-m-d') . ' ' . self::extractTimeOnly($schedule->start_time), $empTz);
                $schedEndLocal = Carbon::parse($schedule->shift_date->format('Y-m-d') . ' ' . self::extractTimeOnly($schedule->end_time), $empTz);
                if ($schedEndLocal->lte($schedStartLocal)) {
                    $schedEndLocal->addDay();
                }
                $assignedShiftEndTimeUtc = $schedEndLocal->copy()->setTimezone('UTC');
            } elseif ($jobShift && isset($jobShift['shift_hours']) && (float) $jobShift['shift_hours'] > 0) {
                $shiftMinutes = (int) round(((float) $jobShift['shift_hours']) * 60);
                $assignedShiftEndTimeUtc = $clockInUtc->copy()->addMinutes($shiftMinutes);
            } elseif ($restrictBeyondShift && $employee->standard_shift_hours && (float) $employee->standard_shift_hours > 0) {
                $shiftMinutes = (int) round(((float) $employee->standard_shift_hours) * 60);
                $assignedShiftEndTimeUtc = $clockInUtc->copy()->addMinutes($shiftMinutes);
            }

            if ($restrictBeyondShift && $assignedShiftEndTimeUtc && $nowUtc->gte($assignedShiftEndTimeUtc)) {
                $autoClockedOut = true;
                $autoClockOutTimeUtc = $assignedShiftEndTimeUtc;
                $isUnusual = false;
                $reason = "Auto clocked out by the end of assigned shift time (tracking beyond assigned hours is not permitted).";
            }

            // --- RULE A: Clocked in prior to new day shift commencement ---
            $subsequentShift = Schedule::where('employee_id', $employee->id)
                ->published()
                ->where(function ($q) use ($clockInDate, $clockInLocal) {
                    $q->whereDate('shift_date', '>', $clockInDate)
                      ->orWhere(function ($q2) use ($clockInDate, $clockInLocal) {
                          $q2->whereDate('shift_date', '=', $clockInDate)
                             ->whereTime('start_time', '>', $clockInLocal->format('H:i:s'));
                      });
                })
                ->orderBy('shift_date')
                ->orderBy('start_time')
                ->first();

            if ($subsequentShift) {
                $nextShiftStartLocal = Carbon::parse($subsequentShift->shift_date->format('Y-m-d') . ' ' . self::extractTimeOnly($subsequentShift->start_time), $empTz);
                $nextShiftStartUtc = $nextShiftStartLocal->copy()->setTimezone('UTC');

                if ($nowUtc->gte($nextShiftStartUtc->copy()->subMinute())) {
                    $autoClockedOut = true;
                    $autoClockOutTimeUtc = $nextShiftStartUtc->copy()->subMinute();
                    if ($autoClockOutTimeUtc->lt($clockInUtc)) {
                        $autoClockOutTimeUtc = $nextShiftStartUtc;
                    }
                    $isUnusual = true;
                    $reason = "Auto clocked out before commencement of the new day shift.";
                }
            } elseif ($todayDate > $clockInDate) {
                $stdStart = $employee->standard_shift_start ?: '08:00';
                $newDayShiftStartLocal = Carbon::parse($todayDate . ' ' . self::extractTimeOnly($stdStart), $empTz);
                $newDayShiftStartUtc = $newDayShiftStartLocal->copy()->setTimezone('UTC');

                if ($nowUtc->gte($newDayShiftStartUtc) || $nowLocal->diffInHours($clockInLocal) >= 12) {
                    $autoClockedOut = true;
                    $autoClockOutTimeUtc = $newDayShiftStartUtc->copy()->subMinute();
                    if ($autoClockOutTimeUtc->lt($clockInUtc)) {
                        $autoClockOutTimeUtc = $clockInUtc->copy()->addHours(8);
                    }
                    if ($autoClockOutTimeUtc->gt($nowUtc)) {
                        $autoClockOutTimeUtc = $nowUtc;
                    }
                    $isUnusual = true;
                    $reason = "Auto clocked out before commencement of the new day shift.";
                }
            }

            if ($autoClockedOut && $autoClockOutTimeUtc) {
                if ($log->isOnBreak()) {
                    $breakStart = $log->getBreakStartedAtCarbon();
                    $diffSecs = $breakStart ? max(0, $autoClockOutTimeUtc->getTimestamp() - $breakStart->getTimestamp()) : 0;
                    $breaks = $log->breaks ?? [];
                    $breaks[] = [
                        'start' => $breakStart ? $breakStart->toIso8601String() : $autoClockOutTimeUtc->toIso8601String(),
                        'end' => $autoClockOutTimeUtc->toIso8601String(),
                        'seconds' => $diffSecs,
                        'minutes' => (int) round($diffSecs / 60),
                    ];
                    $log->breaks = $breaks;
                    $log->break_minutes = (int) round($log->completedBreakSeconds() / 60);
                    $log->break_started_at = null;
                }

                $log->clock_out = $autoClockOutTimeUtc;
                $log->duration_minutes = (int) ceil($log->netSeconds() / 60);
                $log->status = 'completed';
                $log->notes = $log->notes ? ($log->notes . ' | auto clocked out') : 'auto clocked out';

                if ($isUnusual) {
                    $log->is_unusual = true;
                    $log->unusual_reason = $log->unusual_reason ? ($log->unusual_reason . ' | ' . $reason) : $reason;
                }

                $log->save();

                try {
                    \App\Models\TimeLogNote::create([
                        'time_log_id' => $log->id,
                        'user_id' => $employee->id,
                        'body' => 'auto clocked out',
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("Failed to create auto clocked out note: " . $e->getMessage());
                }

                self::processTimeLogSubmission($log);
                $processed[] = $log->id;
            }
        }

        return [
            'auto_clocked_out_count' => count($processed),
            'log_ids' => $processed,
        ];
    }

    /**
     * Safely extract the time component (HH:MM:SS or HH:MM) from a string that might be a full datetime or time.
     */
    private static function extractTimeOnly(?string $time): string
    {
        if (empty($time)) {
            return '00:00:00';
        }
        $trimmed = trim($time);
        if (str_contains($trimmed, ' ')) {
            $parts = explode(' ', $trimmed);
            return end($parts);
        }
        return $trimmed;
    }
}
