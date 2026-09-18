<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TimeClockController;
use App\Http\Controllers\ProfileController;

use App\Livewire\Employees;
use App\Livewire\Departments;
use App\Livewire\Locations;

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
    Route::view('/reports',    'reports')->name('reports');
    Route::view('/map',        'map')->name('map');
    Route::view('/groups',     'groups')->name('groups');

    // Time → Timesheets submenu
    Route::view('/timesheets/view',    'timesheets.view')->name('timesheets.view');
    Route::view('/timesheets/approve', 'timesheets.approve')->name('timesheets.approve');

    // Time → Time Off submenu
    Route::view('/time-off/policies',     'time-off.policies')->name('timeoff.policies');
    Route::view('/time-off/requests',     'time-off.requests')->name('timeoff.requests');
    Route::view('/time-off/policies/add', 'time-off.policy-wizard')->name('timeoff.policies.add');
    Route::get('/time-off/policies/{id}/edit',
        fn ($id) => view('time-off.policy-wizard', ['policyId' => (int) $id])
    )->name('timeoff.policies.edit');

    // Work submenu
    Route::view('/work',          'work')->name('work');
    Route::view('/work/customers', 'work.customers')->name('work.customers');
    Route::view('/work/jobs',      'work.jobs')->name('work.jobs');
    Route::view('/work/tasks',     'work.tasks')->name('work.tasks');
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
