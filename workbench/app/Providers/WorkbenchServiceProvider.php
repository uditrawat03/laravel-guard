<?php

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config()->set('cache.default', 'array');
        config()->set('queue.default', 'sync');
        config()->set('laravel-guard.ui.middleware', []);
        config()->set('laravel-guard.ui.allow_scan', false);
        config()->set('laravel-guard.ui.scan_on_first_view', false);
        config()->set('laravel-guard.ui.per_page', 10);
    }

    public function boot(): void
    {
        Gate::define('viewLaravelGuard', fn ($user = null): bool => true);
    }
}
