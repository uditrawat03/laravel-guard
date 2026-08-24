<?php

namespace LaravelGuard;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use LaravelGuard\Commands\BaselineCommand;
use LaravelGuard\Commands\BenchmarkCommand;
use LaravelGuard\Commands\CheckCommand;
use LaravelGuard\Commands\DiffCommand;
use LaravelGuard\Commands\DoctorCommand;
use LaravelGuard\Commands\ExplainRuleCommand;
use LaravelGuard\Commands\ListRulesCommand;
use LaravelGuard\Commands\RuntimeBenchmarkCommand;
use LaravelGuard\Commands\ScanCommand;
use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Diagnostics\ConfigurationIssueBag;
use LaravelGuard\Core\Diagnostics\DiagnosticResult;
use LaravelGuard\Core\Diagnostics\DiagnosticStatus;
use LaravelGuard\Core\Exceptions\SecurityExceptionManager;
use LaravelGuard\Core\Rules\RuleRegistry;
use LaravelGuard\Core\Source\SourceIndex;
use LaravelGuard\Runtime\SecurityEventCollector;
use LaravelGuard\Tenant\Contracts\TenantResolver;
use LaravelGuard\Tenant\TenantContext;
use LaravelGuard\Tenant\TenantQueryInspector;
use LaravelGuard\Ui\FileScanRunRepository;
use LaravelGuard\Ui\Http\Middleware\AuthorizeDashboard;
use LaravelGuard\Ui\ScanRunRepository;
use LaravelGuard\Uploads\Runtime\InspectUploadedFiles;

final class LaravelGuardServiceProvider extends ServiceProvider
{
    private const RULES = [
        Configuration\Rules\ProductionDebugEnabled::class, Configuration\Rules\WeakSessionConfiguration::class,
        Configuration\Rules\OverlyBroadCors::class, Configuration\Rules\MissingApplicationKey::class,
        Configuration\Rules\PublicSensitiveFilesystem::class, Configuration\Rules\InsecureLoggingConfiguration::class,
        Configuration\Rules\MissingDatabaseTls::class, Configuration\Rules\InsecureMailTransport::class,
        Configuration\Rules\OverlyTrustedProxies::class,
        Routes\Rules\MissingAuthentication::class, Routes\Rules\MissingAuthorization::class,
        Routes\Rules\MissingRateLimit::class, Routes\Rules\PublicAdministrativeRoute::class,
        Routes\Rules\SensitiveGetAction::class, Routes\Rules\UnsignedSensitiveAction::class,
        Routes\Rules\MissingPolicyRegistration::class,
        Uploads\Rules\MissingUploadValidation::class, Uploads\Rules\UserControlledFilename::class,
        Uploads\Rules\DangerousUploadExtension::class, Uploads\Rules\PublicExecutableUpload::class,
        Uploads\Rules\MissingUploadSizeRestriction::class, Uploads\Rules\UploadPathTraversal::class,
        Uploads\Rules\UnsanitizedSvgUpload::class,
        Tenant\Rules\MissingTenantConstraint::class, Tenant\Rules\CrossTenantAccess::class,
        Tenant\Rules\MissingTenantContext::class, Tenant\Rules\UnsafeTenantUpdate::class,
        Tenant\Rules\UnsafeTenantDelete::class, Tenant\Rules\UnsafeRawTenantQuery::class,
        Queries\Rules\PotentialSqlInjection::class, Queries\Rules\UnsafeRawSql::class,
        Queries\Rules\UnsafeBulkUpdate::class, Queries\Rules\UnsafeBulkDelete::class,
        Models\Rules\UnsafeMassAssignment::class, Models\Rules\SensitiveAttributeExposure::class,
        Secrets\Rules\HardcodedSecret::class, Secrets\Rules\CommittedCredential::class,
        Api\Rules\MissingApiAuthentication::class, Api\Rules\MissingApiRateLimit::class,
        Api\Rules\UnsafeApiResourceExposure::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-guard.php', 'laravel-guard');
        $this->app->singleton(RuleRegistry::class);
        $this->app->singleton(SourceIndex::class);
        $this->app->singleton(ConfigurationIssueBag::class);
        $this->app->scoped(SecurityEventCollector::class);
        $this->app->singleton(SecurityExceptionManager::class);
        $this->app->singleton(SecurityContext::class, fn ($app) => new SecurityContext($app, $app['config']->get('laravel-guard', [])));
        $this->app->scoped(TenantContext::class, function ($app) {
            $resolver = $app['config']->get('laravel-guard.tenant.resolver');

            return new TenantContext($resolver ? $app->make($resolver) : ($app->bound(TenantResolver::class) ? $app->make(TenantResolver::class) : null));
        });
        $this->app->singleton(LaravelGuard::class);
        $this->app->singleton(ScanRunRepository::class, FileScanRunRepository::class);
    }

    public function boot(RuleRegistry $registry, ConfigurationIssueBag $issues): void
    {
        $this->publishes([__DIR__.'/../config/laravel-guard.php' => config_path('laravel-guard.php')], 'laravel-guard-config');
        $this->app['router']->aliasMiddleware('guard.uploads', InspectUploadedFiles::class);
        $this->app['router']->aliasMiddleware('laravel-guard.ui.authorize', AuthorizeDashboard::class);
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-guard');
        if ((bool) config('laravel-guard.ui.enabled', false)) {
            $this->configureUiRateLimiting();
            $this->loadRoutesFrom(__DIR__.'/../routes/ui.php');
        }
        foreach (self::RULES as $rule) {
            $registry->register($rule);
        }
        foreach ((array) config('laravel-guard.custom_rules', []) as $rule) {
            try {
                $registry->register($rule);
            } catch (\Throwable $error) {
                $label = is_scalar($rule) ? (string) $rule : get_debug_type($rule);
                $issues->add(new DiagnosticResult(
                    DiagnosticStatus::Error,
                    'custom_rules',
                    "Custom rule [{$label}] could not be registered: {$error->getMessage()}",
                    'Register a resolvable class implementing LaravelGuard\\Core\\Contracts\\GuardRule.',
                ));
            }
        }
        if ($this->runtimeEnabled()) {
            $this->app['db']->listen(function (QueryExecuted $query): void {
                app(TenantQueryInspector::class)->inspect($query, config('laravel-guard.tenant.tables', []), config('laravel-guard.tenant.column', 'tenant_id'));
            });
        }
        if ($this->app->runningInConsole()) {
            $this->commands([
                ScanCommand::class, CheckCommand::class, DiffCommand::class, BaselineCommand::class,
                ListRulesCommand::class, BenchmarkCommand::class, RuntimeBenchmarkCommand::class, DoctorCommand::class, ExplainRuleCommand::class,
            ]);
        }
    }

    private function configureUiRateLimiting(): void
    {
        RateLimiter::for('laravel-guard-ui', function (Request $request): Limit {
            return Limit::perMinute(max(1, (int) config('laravel-guard.ui.read_rate_limit', 240)))
                ->by('laravel-guard-ui:'.$this->rateLimitIdentity($request));
        });

        RateLimiter::for('laravel-guard-ui-scan', function (Request $request): Limit {
            return Limit::perMinute(max(1, (int) config('laravel-guard.ui.scan_rate_limit', 3)))
                ->by('laravel-guard-ui-scan:'.$this->rateLimitIdentity($request));
        });
    }

    private function rateLimitIdentity(Request $request): string
    {
        $user = $request->user();

        return $user !== null && method_exists($user, 'getAuthIdentifier')
            ? 'user:'.(string) $user->getAuthIdentifier()
            : 'ip:'.(string) $request->ip();
    }

    private function runtimeEnabled(): bool
    {
        return config('laravel-guard.runtime.enabled', false)
            && $this->app->environment(config('laravel-guard.runtime.environments', ['local', 'testing']));
    }
}
