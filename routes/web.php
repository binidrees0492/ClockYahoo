<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportPrintController;
use App\Http\Controllers\TimeClockController;
use App\Livewire\Departments;
use App\Livewire\Employees;
use App\Livewire\Locations;
use App\Livewire\Reports;
use App\Livewire\TimeOff\Index as TimeOffIndex;
use App\Livewire\TimeOff\PolicyWizard;
use App\Livewire\TimeOff\RequestManager;
use App\Livewire\Work\Jobs;
use App\Livewire\Work\Tasks;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {

    // Main pages
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/time-clock', [TimeClockController::class, 'index'])->name('timeclock.index');

    // Mail & Logs Modules
    Route::get('/webmail', \App\Livewire\Webmail::class)->name('webmail');
    Route::get('/mail-logs', \App\Livewire\MailLogs::class)->name('mail.logs');
    Route::get('/notifications-log', \App\Livewire\NotificationsLog::class)->name('notifications.log');
    Route::get('/notification-settings', \App\Livewire\NotificationSettings::class)->name('notification.settings');

    // Executive Dashboard
    Route::get('/executive-dashboard', \App\Livewire\ExecutiveDashboard::class)->name('executive.dashboard');

    // Admin Management Pages (Admin & HR Manager only)
    Route::middleware('permission:admin')->group(function () {
        Route::get('/admin/employees', Employees::class)->name('admin.employees');
        Route::get('/admin/departments', Departments::class)->name('admin.departments');
        Route::get('/admin/locations', Locations::class)->name('admin.locations');
        Route::get('/admin/access-management', \App\Livewire\AccessManagement::class)->name('admin.access-management');
        Route::get('/admin/audit-log', \App\Livewire\AuditLog::class)->name('admin.audit-log');
        Route::get('/admin/company-settings', \App\Livewire\CompanySettings::class)->name('admin.company-settings');
        Route::get('/admin/notifications', \App\Livewire\NotificationSettings::class)->name('admin.notifications');
        Route::get('/admin/data-shedding', \App\Livewire\DataShedding::class)->name('admin.data-shedding');
        Route::get('/admin/inactive-records', \App\Livewire\InactiveRecordsManager::class)->name('admin.inactive-records');
        Route::get('/admin', fn() => redirect()->route('admin.employees'));

        // Time Off Policies Management
        Route::get('/time-off/policies', TimeOffIndex::class)->defaults('tab', 'policies')->name('timeoff.policies');
        Route::get('/time-off/policies/add', PolicyWizard::class)->name('timeoff.policies.add');
        Route::get('/time-off/policies/{policyId}/edit', PolicyWizard::class)->name('timeoff.policies.edit');
    });

    // Time → Timesheets
    Route::middleware('permission:timesheets')->group(function () {
        Route::view('/timesheets', 'timesheets')->name('timesheets');
        Route::view('/timesheets/view', 'timesheets.view')->name('timesheets.view');
    });

    // Time → Approve Timesheets
    Route::middleware('permission:approve_timesheets')->group(function () {
        Route::view('/timesheets/approve', 'timesheets.approve')->name('timesheets.approve');
    });

    // Time → Time Off Requests (Available to all authenticated employees)
    Route::get('/time-off/requests', RequestManager::class)->name('timeoff.requests');

    // Schedules
    Route::middleware('permission:schedules')->group(function () {
        Route::view('/schedules', 'schedules')->name('schedules');
    });

    // Reports
    Route::middleware('permission:reports')->group(function () {
        Route::get('/reports', Reports::class)->name('reports');
        Route::get('/reports/print', [ReportPrintController::class, 'generatePdf'])->name('reports.print');
    });

    // Map
    Route::middleware('permission:map')->group(function () {
        Route::view('/map', 'map')->name('map');
    });

    Route::view('/groups', 'groups')->name('groups');

    // Work submenu
    Route::view('/work', 'work')->name('work');

    Route::middleware('permission:view,customers')->group(function () {
        Route::get('/work/customers', \App\Livewire\Work\Customers::class)->name('work.customers');
    });

    Route::middleware('permission:view,jobs')->group(function () {
        Route::get('/work/jobs', Jobs::class)->name('work.jobs');
    });

    Route::middleware('permission:view,tasks')->group(function () {
        Route::get('/work/tasks', Tasks::class)->name('work.tasks');
    });

    Route::middleware('permission:view,quotes')->group(function () {
        Route::get('/work/quotes', \App\Livewire\Work\Quotes::class)->name('work.quotes');
    });

    Route::middleware('permission:view,invoices')->group(function () {
        Route::get('/work/invoices', \App\Livewire\Work\Invoices::class)->name('work.invoices');
    });

    Route::get('/work/employees', fn() => redirect()->route('admin.employees'))->name('work.employees');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Public storage static file server (fail-safe for live environments without storage:link or symlink support)
Route::get('/storage/{path}', function (string $path) {
    // Prevent directory traversal attacks
    $cleanPath = str_replace(['../', '..\\'], '', $path);
    $cleanPath = ltrim($cleanPath, '/\\');

    $candidates = [
        \Illuminate\Support\Facades\Storage::disk('public')->path($cleanPath),
        storage_path('app/public/' . $cleanPath),
        storage_path('app/' . $cleanPath),
        public_path('storage/' . $cleanPath),
        public_path($cleanPath),
        storage_path('app/public/company/' . $cleanPath),
        storage_path('app/public/avatars/' . $cleanPath),
    ];

    $foundPath = null;
    foreach ($candidates as $candidate) {
        if (file_exists($candidate) && is_file($candidate)) {
            $foundPath = $candidate;
            break;
        }
    }

    if (!$foundPath) {
        abort(404);
    }

    $mimeType = match (strtolower(pathinfo($foundPath, PATHINFO_EXTENSION))) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png'         => 'image/png',
        'gif'         => 'image/gif',
        'svg'         => 'image/svg+xml',
        'webp'        => 'image/webp',
        'ico'         => 'image/x-icon',
        'pdf'         => 'application/pdf',
        default       => mime_content_type($foundPath) ?: 'application/octet-stream',
    };

    return response()->file($foundPath, [
        'Content-Type'                => $mimeType,
        'Cache-Control'               => 'public, max-age=31536000, immutable',
        'Access-Control-Allow-Origin' => '*',
    ]);
})->where('path', '.*')->name('storage.file');

// PWA / static icons fail-safe (serves public/icons/* when Apache doesn't)
Route::get('/icons/{path}', function (string $path) {
    $cleanPath = str_replace(['../', '..\\'], '', $path);
    $cleanPath = ltrim($cleanPath, '/\\');
    $filePath  = public_path('icons/' . $cleanPath);

    if (!file_exists($filePath) || !is_file($filePath)) {
        abort(404);
    }

    $mimeType = match (strtolower(pathinfo($filePath, PATHINFO_EXTENSION))) {
        'png'  => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'webp' => 'image/webp',
        default => mime_content_type($filePath) ?: 'application/octet-stream',
    };

    return response()->file($filePath, [
        'Content-Type'                => $mimeType,
        'Cache-Control'               => 'public, max-age=31536000, immutable',
        'Access-Control-Allow-Origin' => '*',
    ]);
})->where('path', '.*')->name('icons.file');

Route::fallback(function () {
    return view('errors.404');
});

require __DIR__ . '/auth.php';
