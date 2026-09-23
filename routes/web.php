<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TimeClockController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportPrintController;

use App\Livewire\Employees;
use App\Livewire\Departments;
use App\Livewire\Locations;
use App\Livewire\TimeOff\Index as TimeOffIndex;
use App\Livewire\TimeOff\PolicyWizard;
use App\Livewire\Work\Jobs;
use App\Livewire\Work\Tasks;
use App\Livewire\Reports;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {

    // Main pages
    Route::get('/dashboard',  [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/time-clock', [TimeClockController::class, 'index'])->name('timeclock.index');

    // Admin Management Pages (Livewire Full-Page Routes)
    Route::get('/admin/employees', Employees::class)->name('admin.employees');
    Route::get('/admin/departments', Departments::class)->name('admin.departments');
    Route::get('/admin/locations', Locations::class)->name('admin.locations');
    Route::get('/admin', fn() => redirect()->route('admin.employees'));

    Route::view('/timesheets', 'timesheets')->name('timesheets');
    Route::view('/schedules',  'schedules')->name('schedules');
    Route::get('/reports',     Reports::class)->name('reports');
    Route::get('/reports/print', [ReportPrintController::class, 'generatePdf'])->name('reports.print');

    Route::view('/map',        'map')->name('map');
    Route::view('/groups',     'groups')->name('groups');

    // Time → Timesheets submenu
    Route::view('/timesheets/view',    'timesheets.view')->name('timesheets.view');
    Route::view('/timesheets/approve', 'timesheets.approve')->name('timesheets.approve');

    // Time → Time Off submenu (Livewire Full-Page Routes)
    Route::get('/time-off/policies', TimeOffIndex::class)->defaults('tab', 'policies')->name('timeoff.policies');
    Route::get('/time-off/requests', TimeOffIndex::class)->defaults('tab', 'requests')->name('timeoff.requests');
    Route::get('/time-off/policies/add', PolicyWizard::class)->name('timeoff.policies.add');
    Route::get('/time-off/policies/{policyId}/edit', PolicyWizard::class)->name('timeoff.policies.edit');

    // Work submenu
    Route::view('/work',          'work')->name('work');
    Route::view('/work/customers', 'work.customers')->name('work.customers');
    Route::get('/work/jobs',      Jobs::class)->name('work.jobs');
    Route::get('/work/tasks',     Tasks::class)->name('work.tasks');
    Route::view('/work/quotes',    'work.quotes')->name('work.quotes');
    Route::view('/work/invoices',  'work.invoices')->name('work.invoices');
    Route::view('/work/employees', 'work.employees')->name('work.employees');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
