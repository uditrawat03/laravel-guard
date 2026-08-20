<?php

namespace LaravelGuard;

use Illuminate\Support\ServiceProvider;
use LaravelGuard\Commands\BaselineCommand;
use LaravelGuard\Commands\CheckCommand;
use LaravelGuard\Commands\ListRulesCommand;
use LaravelGuard\Commands\ScanCommand;
use LaravelGuard\Configuration\Rules\OverlyBroadCors;
use LaravelGuard\Configuration\Rules\ProductionDebugEnabled;
use LaravelGuard\Configuration\Rules\WeakSessionConfiguration;
use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Rules\RuleRegistry;
use LaravelGuard\Routes\Rules\MissingAuthentication;
use LaravelGuard\Routes\Rules\MissingAuthorization;
use LaravelGuard\Routes\Rules\MissingRateLimit;
use LaravelGuard\Tenant\Contracts\TenantResolver;
use LaravelGuard\Tenant\Rules\MissingTenantConstraint;
use LaravelGuard\Tenant\Rules\MissingTenantContext;
use LaravelGuard\Tenant\TenantContext;
use LaravelGuard\Uploads\Rules\MissingUploadValidation;
use LaravelGuard\Uploads\Rules\UserControlledFilename;

final class LaravelGuardServiceProvider extends ServiceProvider
{
    private const RULES = [
        ProductionDebugEnabled::class, WeakSessionConfiguration::class, OverlyBroadCors::class,
        MissingAuthentication::class, MissingAuthorization::class, MissingRateLimit::class,
        MissingUploadValidation::class, UserControlledFilename::class,
        MissingTenantConstraint::class, MissingTenantContext::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-guard.php', 'laravel-guard');
        $this->app->singleton(RuleRegistry::class);
        $this->app->singleton(SecurityContext::class, fn ($app) => new SecurityContext($app, $app['config']->get('laravel-guard', [])));
        $this->app->singleton(TenantContext::class, function ($app) {
            $resolver = $app['config']->get('laravel-guard.tenant.resolver');

            return new TenantContext($resolver ? $app->make($resolver) : ($app->bound(TenantResolver::class) ? $app->make(TenantResolver::class) : null));
        });
        $this->app->singleton(LaravelGuard::class);
    }

    public function boot(RuleRegistry $registry): void
    {
        $this->publishes([__DIR__.'/../config/laravel-guard.php' => config_path('laravel-guard.php')], 'laravel-guard-config');
        foreach ([...self::RULES, ...config('laravel-guard.custom_rules', [])] as $rule) {
            $registry->register($rule);
        }
        if ($this->app->runningInConsole()) {
            $this->commands([ScanCommand::class, CheckCommand::class, BaselineCommand::class, ListRulesCommand::class]);
        }
    }
}
