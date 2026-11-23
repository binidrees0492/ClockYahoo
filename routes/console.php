<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('timeclock:auto-clockout', function () {
    $this->info('Running automatic clock-out compliance check...');
    $result = \App\Services\TimeLogComplianceService::enforceAutoClockOut();
    $count = $result['auto_clocked_out_count'] ?? 0;
    $this->info("Completed. Auto clocked out {$count} open time session(s).");
})->purpose('Enforce automatic clock-out for shifts commencing a new day or exceeding assigned hours');

