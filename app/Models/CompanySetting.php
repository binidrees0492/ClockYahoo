<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory;

    protected $table = 'company_settings';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        $val = $setting->value;
        // Try decoding json if array/object
        if ($val !== null && (str_starts_with($val, '{') || str_starts_with($val, '['))) {
            $decoded = json_decode($val, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $val;
    }

    public static function set(string $key, $value): void
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        static::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value]
        );

    }

    public static function getServerTimezone(): string
    {
        return static::get('server_timezone', 'UTC') ?: 'UTC';
    }

    public static function getLocationTrackingPolicy(): string
    {
        // 'none' | 'flag' | 'restrict'
        return static::get('location_tracking_policy', 'flag');
    }

    public static function getDefaultGeofenceRadius(): int
    {
        return (int) static::get('default_geofence_radius', 200);
    }

    public static function getWeekStartDay(): int
    {
        // Carbon::SUNDAY = 0, Carbon::MONDAY = 1, Carbon::THURSDAY = 4
        return (int) static::get('week_start_day', \Carbon\Carbon::THURSDAY);
    }

    public static function getBaseTheme(): string
    {
        // 'classic' (ClockShark design) or 'modern' (Next-Gen React SaaS design)
        return strtolower((string) static::get('application_base_theme', 'classic'));
    }

    public static function setBaseTheme(string $theme): void
    {
        $theme = in_array(strtolower($theme), ['classic', 'modern']) ? strtolower($theme) : 'classic';
        static::set('application_base_theme', $theme);
    }

    public static function isModernTheme(): bool
    {
        return static::getBaseTheme() === 'modern';
    }

    public static function getLogoUrl(): ?string
    {
        $logo = static::get('company_logo');
        if (!$logo) {
            return null;
        }
        if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://') || str_starts_with($logo, 'data:')) {
            return $logo;
        }

        $clean = ltrim($logo, '/\\');
        $clean = preg_replace('#^(public/|storage/)+#', '', $clean);

        return url('storage/' . $clean);
    }

    public static function getFaviconUrl(): string
    {
        $fav = static::get('company_favicon') ?: static::get('company_logo');
        if (!$fav) {
            return url('icons/icon-192.png');
        }
        if (str_starts_with($fav, 'http://') || str_starts_with($fav, 'https://') || str_starts_with($fav, 'data:')) {
            return $fav;
        }

        $clean = ltrim($fav, '/\\');
        $clean = preg_replace('#^(public/|storage/)+#', '', $clean);

        return url('storage/' . $clean);
    }

    public static function getBuiltinFonts(): array
    {
        return [
            'opensans' => [
                'id' => 'opensans',
                'name' => 'Open Sans (ClockShark Default)',
                'css' => "'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
                'category' => 'ClockShark Standard',
                'google_url' => 'https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,600;1,700&display=swap',
                'is_custom' => false,
            ],
            'aptos' => [
                'id' => 'aptos',
                'name' => 'Aptos',
                'css' => "'Aptos', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
                'category' => 'Modern Sans',
                'google_url' => null,
                'is_custom' => false,
            ],
            'inter' => [
                'id' => 'inter',
                'name' => 'Inter',
                'css' => "'Inter', -apple-system, BlinkMacSystemFont, sans-serif",
                'category' => 'Neutral Sans',
                'google_url' => 'https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400;1,700&display=swap',
                'is_custom' => false,
            ],
            'roboto' => [
                'id' => 'roboto',
                'name' => 'Roboto',
                'css' => "'Roboto', sans-serif",
                'category' => 'Geometric Sans',
                'google_url' => 'https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,400;1,700&display=swap',
                'is_custom' => false,
            ],
            'poppins' => [
                'id' => 'poppins',
                'name' => 'Poppins',
                'css' => "'Poppins', sans-serif",
                'category' => 'Geometric Display',
                'google_url' => 'https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400;1,700&display=swap',
                'is_custom' => false,
            ],
            'montserrat' => [
                'id' => 'montserrat',
                'name' => 'Montserrat',
                'css' => "'Montserrat', sans-serif",
                'category' => 'Urban Sans',
                'google_url' => 'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;1,400;1,700&display=swap',
                'is_custom' => false,
            ],
            'lato' => [
                'id' => 'lato',
                'name' => 'Lato',
                'css' => "'Lato', sans-serif",
                'category' => 'Warm Sans',
                'google_url' => 'https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,300;0,400;0,700;1,400;1,700&display=swap',
                'is_custom' => false,
            ],
            'raleway' => [
                'id' => 'raleway',
                'name' => 'Raleway',
                'css' => "'Raleway', sans-serif",
                'category' => 'Elegant Sans',
                'google_url' => 'https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,400;0,500;0,600;0,700;1,400;1,700&display=swap',
                'is_custom' => false,
            ],
            'nunito' => [
                'id' => 'nunito',
                'name' => 'Nunito',
                'css' => "'Nunito', sans-serif",
                'category' => 'Rounded Sans',
                'google_url' => 'https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,400;0,600;0,700;1,400;1,700&display=swap',
                'is_custom' => false,
            ],
            'plusjakartasans' => [
                'id' => 'plusjakartasans',
                'name' => 'Plus Jakarta Sans',
                'css' => "'Plus Jakarta Sans', sans-serif",
                'category' => 'Modern Tech Sans',
                'google_url' => 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400;1,700&display=swap',
                'is_custom' => false,
            ],
            'playfair' => [
                'id' => 'playfair',
                'name' => 'Playfair Display',
                'css' => "'Playfair Display', Georgia, serif",
                'category' => 'Editorial Serif',
                'google_url' => 'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,700&display=swap',
                'is_custom' => false,
            ],
            'merriweather' => [
                'id' => 'merriweather',
                'name' => 'Merriweather',
                'css' => "'Merriweather', Georgia, serif",
                'category' => 'Readable Serif',
                'google_url' => 'https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,300;0,400;0,700;1,400;1,700&display=swap',
                'is_custom' => false,
            ],
            'firacode' => [
                'id' => 'firacode',
                'name' => 'Fira Code',
                'css' => "'Fira Code', monospace",
                'category' => 'Monospace Tech',
                'google_url' => 'https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;700&display=swap',
                'is_custom' => false,
            ],
            'system' => [
                'id' => 'system',
                'name' => 'System UI Default',
                'css' => "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
                'category' => 'Native OS',
                'google_url' => null,
                'is_custom' => false,
            ],
        ];
    }

    public static function getAvailableFonts(): array
    {
        $fonts = static::getBuiltinFonts();
        $customFonts = static::get('custom_fonts', []);

        if (is_array($customFonts)) {
            foreach ($customFonts as $id => $font) {
                if (is_array($font) && isset($font['name'])) {
                    $fontId = $font['id'] ?? (string) $id;
                    $fonts[$fontId] = [
                        'id' => $fontId,
                        'name' => $font['name'],
                        'css' => $font['css'] ?? "'{$font['name']}', sans-serif",
                        'category' => $font['category'] ?? 'Custom',
                        'google_url' => $font['google_url'] ?? null,
                        'is_custom' => true,
                    ];
                }
            }
        }

        return $fonts;
    }

    public static function addCustomFont(string $name, string $cssFamily, ?string $googleUrl = null): string
    {
        $slug = \Illuminate\Support\Str::slug($name);
        $fontId = 'custom_' . $slug;
        $customFonts = static::get('custom_fonts', []);
        if (!is_array($customFonts)) {
            $customFonts = [];
        }

        $customFonts[$fontId] = [
            'id' => $fontId,
            'name' => trim($name),
            'css' => trim($cssFamily),
            'category' => 'Custom Font',
            'google_url' => $googleUrl ? trim($googleUrl) : null,
            'is_custom' => true,
            'created_at' => now()->toIso8601String(),
        ];

        static::set('custom_fonts', $customFonts);
        return $fontId;
    }

    public static function removeCustomFont(string $fontId): bool
    {
        $customFonts = static::get('custom_fonts', []);
        if (is_array($customFonts) && isset($customFonts[$fontId])) {
            unset($customFonts[$fontId]);
            static::set('custom_fonts', $customFonts);
            return true;
        }
        return false;
    }

    public static function getFontCss(string $fontId): string
    {
        $fonts = static::getAvailableFonts();
        return $fonts[$fontId]['css'] ?? $fonts['opensans']['css'] ?? "'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
    }

    public static function getAllNavPages(): array
    {
        return [
            // Time Tracking
            'dashboard' => [
                'name' => 'My Time Clock',
                'category' => 'Time Tracking',
                'description' => 'Main employee time clock, active shift timer, and personal schedule',
                'icon' => 'fa-solid fa-clock',
                'route' => 'dashboard',
            ],
            'timesheets_view' => [
                'name' => 'View Timesheets',
                'category' => 'Time Tracking',
                'description' => 'Detailed multi-day timesheet grid, switch logs, and hours summary',
                'icon' => 'fa-solid fa-table-list',
                'route' => 'timesheets.view',
            ],
            'timesheets_approve' => [
                'name' => 'Approve Timesheets',
                'category' => 'Time Tracking',
                'description' => 'Supervisor approval queue, bulk approve, and disapproval notes',
                'icon' => 'fa-solid fa-check-double',
                'route' => 'timesheets.approve',
            ],
            'timeoff_requests' => [
                'name' => 'Time Off Requests',
                'category' => 'Time Tracking',
                'description' => 'Employee PTO/leave requests and manager discussion threads',
                'icon' => 'fa-solid fa-plane-departure',
                'route' => 'timeoff.requests',
            ],
            'timeoff_policies' => [
                'name' => 'PTO Policies',
                'category' => 'Time Tracking',
                'description' => 'Accrual policies, carryover limits, and employee allocations',
                'icon' => 'fa-solid fa-scale-balanced',
                'route' => 'timeoff.policies',
            ],

            // Scheduling
            'schedules' => [
                'name' => 'Schedules Board & Calendar',
                'category' => 'Scheduling',
                'description' => 'Shift scheduling calendar, weekly board, and auto-populated GPS pins',
                'icon' => 'fa-solid fa-calendar-days',
                'route' => 'schedules',
            ],

            // Work & Operations
            'work_customers' => [
                'name' => 'Customers',
                'category' => 'Work & Operations',
                'description' => 'Customer directory, contact profiles, and job associations',
                'icon' => 'fa-solid fa-building-user',
                'route' => 'work.customers',
            ],
            'work_jobs' => [
                'name' => 'Jobs',
                'category' => 'Work & Operations',
                'description' => 'Project jobs, site addresses, and job status filtering',
                'icon' => 'fa-solid fa-briefcase',
                'route' => 'work.jobs',
            ],
            'work_tasks' => [
                'name' => 'Tasks',
                'category' => 'Work & Operations',
                'description' => 'Job task items, hourly billing rates, and GPS geofence tagging',
                'icon' => 'fa-solid fa-list-check',
                'route' => 'work.tasks',
            ],
            'work_quotes' => [
                'name' => 'Quotes',
                'category' => 'Work & Operations',
                'description' => 'Job cost estimates and customer quote proposals',
                'icon' => 'fa-solid fa-file-invoice-dollar',
                'route' => 'work.quotes',
            ],
            'work_invoices' => [
                'name' => 'Invoices',
                'category' => 'Work & Operations',
                'description' => 'Billing management, recorded payments, and customer invoicing',
                'icon' => 'fa-solid fa-receipt',
                'route' => 'work.invoices',
            ],

            // Reporting & Insights
            'reports' => [
                'name' => 'Reports & Analytics',
                'category' => 'Reporting & Insights',
                'description' => 'Payroll summaries, employee hour breakdowns, and attendance audits',
                'icon' => 'fa-solid fa-chart-pie',
                'route' => 'reports',
            ],
            'executive_dashboard' => [
                'name' => 'Executive Dashboard',
                'category' => 'Reporting & Insights',
                'description' => 'High-level business performance, labor costs, and leadership KPIs',
                'icon' => 'fa-solid fa-gauge-high',
                'route' => 'executive.dashboard',
            ],

            // Location
            'map' => [
                'name' => 'GPS Live Map',
                'category' => 'Location & Maps',
                'description' => 'Real-time GPS workforce map, geofence radius rings, and location presets',
                'icon' => 'fa-solid fa-map-location-dot',
                'route' => 'map',
            ],

            // Communication
            'webmail' => [
                'name' => 'Webmail',
                'category' => 'Communication',
                'description' => 'Integrated employee mailbox for inbound and outbound email',
                'icon' => 'fa-solid fa-envelope',
                'route' => 'webmail',
            ],
            'mail_logs' => [
                'name' => 'Mail Delivery Logs',
                'category' => 'Communication',
                'description' => 'Audit log of sent and failed system email notifications',
                'icon' => 'fa-solid fa-envelope-open-text',
                'route' => 'mail.logs',
            ],
            'notifications_log' => [
                'name' => 'Notifications Log',
                'category' => 'Communication',
                'description' => 'History of shift changes, approvals, and alert notifications',
                'icon' => 'fa-solid fa-bell',
                'route' => 'notifications.log',
            ],

            // Administration
            'admin_employees' => [
                'name' => 'Employees Directory',
                'category' => 'Administration',
                'description' => 'Staff roster, roles, wage rates, emergency contacts, and profile photos',
                'icon' => 'fa-solid fa-users',
                'route' => 'admin.employees',
            ],
            'admin_departments' => [
                'name' => 'Departments',
                'category' => 'Administration',
                'description' => 'Company departments and organizational groupings',
                'icon' => 'fa-solid fa-sitemap',
                'route' => 'admin.departments',
            ],
            'admin_locations' => [
                'name' => 'Locations Presets',
                'category' => 'Administration',
                'description' => 'Workplace sites, office headquarters, and geofence presets',
                'icon' => 'fa-solid fa-location-dot',
                'route' => 'admin.locations',
            ],
            'admin_access_management' => [
                'name' => 'Access Management (UAM)',
                'category' => 'Administration',
                'description' => 'Role-based permission matrix and individual user access overrides',
                'icon' => 'fa-solid fa-shield-halved',
                'route' => 'admin.access-management',
            ],
            'admin_audit_log' => [
                'name' => 'Audit Log',
                'category' => 'Administration',
                'description' => 'System audit trail of edits, changes, and deletions',
                'icon' => 'fa-solid fa-clock-rotate-left',
                'route' => 'admin.audit-log',
            ],
        ];
    }

    public static function getActiveNavPages(): array
    {
        $saved = static::get('active_nav_pages');
        if (is_array($saved)) {
            return $saved;
        }

        // Default: all configured pages are active (true)
        $all = static::getAllNavPages();
        $defaults = [];
        foreach (array_keys($all) as $key) {
            $defaults[$key] = true;
        }
        return $defaults;
    }

    public static function isPageActive(string $pageKey): bool
    {
        $activePages = static::getActiveNavPages();
        if (!array_key_exists($pageKey, $activePages)) {
            return true;
        }
        return (bool) $activePages[$pageKey];
    }

    public static function setActiveNavPages(array $pages): void
    {
        static::set('active_nav_pages', $pages);
    }
}
