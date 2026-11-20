<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enforce application default timezone dynamically across PHP, Carbon, and Laravel
        try {
            $appTz = \App\Models\CompanySetting::getServerTimezone() ?: config('app.timezone', 'America/New_York');
            if ($appTz) {
                @date_default_timezone_set($appTz);
                config(['app.timezone' => $appTz]);
            }
        } catch (\Throwable $e) {
            // Graceful fallback during initial migrations or setup
        }

        // Enforce HTTPS URLs when running in production or behind secure reverse proxies
        if (config('app.env') === 'production' || request()->isSecure() || request()->header('x-forwarded-proto') === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Automatically ensure public storage symlink exists if possible
        if (!file_exists(public_path('storage'))) {
            try {
                \Illuminate\Support\Facades\Artisan::call('storage:link');
            } catch (\Throwable $e) {
                // Fail silently; fallback route handles serving files if symlinks are restricted
            }
        }

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Mail\Events\MessageSent::class,
            \App\Listeners\LogSentMessageListener::class
        );
    }
}
