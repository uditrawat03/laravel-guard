<?php

namespace LaravelGuard\Core\Diagnostics;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use LaravelGuard\Core\Contracts\SecurityReporter;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Integrations\IntegrationManager;
use LaravelGuard\Tenant\Contracts\TenantOwned;
use LaravelGuard\Tenant\Contracts\TenantResolver;
use LaravelGuard\Tenant\GuardsTenant;

final readonly class ConfigurationDoctor
{
    private const MODULES = ['tenant', 'routes', 'configuration', 'uploads', 'queries', 'models', 'secrets', 'api', 'runtime'];

    public function __construct(
        private Application $app,
        private Container $container,
        private Repository $config,
        private IntegrationManager $integrations,
        private ConfigurationIssueBag $bootIssues,
    ) {}

    /** @return list<DiagnosticResult> */
    public function diagnose(): array
    {
        return [
            ...$this->bootIssues->all(),
            ...$this->severities(),
            ...$this->modules(),
            ...$this->paths(),
            ...$this->tenant(),
            ...$this->reporters(),
            ...$this->integrations(),
            ...$this->uploads(),
            ...$this->baseline(),
            ...$this->runtime(),
        ];
    }

    /** @return list<DiagnosticResult> */
    private function severities(): array
    {
        $results = [];
        foreach (['minimum_severity' => 'low', 'ci.fail_on' => 'high'] as $key => $default) {
            $value = (string) $this->config->get("laravel-guard.{$key}", $default);
            try {
                Severity::fromName($value);
                $results[] = $this->pass("severity.{$key}", "[{$value}] is a valid severity.");
            } catch (\InvalidArgumentException) {
                $results[] = $this->error("severity.{$key}", "[{$value}] is not a valid severity.", 'Use low, medium, high, or critical.');
            }
        }

        return $results;
    }

    /** @return list<DiagnosticResult> */
    private function modules(): array
    {
        $configured = $this->config->get('laravel-guard.modules', []);
        if (! is_array($configured)) {
            return [$this->error('modules', 'The modules configuration must be an array.')];
        }

        $results = [];
        foreach ($configured as $module => $enabled) {
            if (! in_array($module, self::MODULES, true)) {
                $results[] = $this->warning('modules', "Unknown module [{$module}] is configured.", 'Remove the key or register rules using an intentional custom category.');
            }
            if (! is_bool($enabled)) {
                $results[] = $this->error("modules.{$module}", 'Module flags must be boolean values.');
            }
        }

        return $results ?: [$this->pass('modules', count($configured).' module flag(s) are valid.')];
    }

    /** @return list<DiagnosticResult> */
    private function paths(): array
    {
        $paths = $this->config->get('laravel-guard.paths', []);
        if (! is_array($paths) || $paths === []) {
            return [$this->error('paths', 'No source scan paths are configured.', 'Add app_path(), route paths, or other trusted project paths.')];
        }

        $results = [];
        foreach ($paths as $path) {
            if (! is_string($path) || ! file_exists($path)) {
                $results[] = $this->error('paths', 'A configured scan path does not exist: '.(is_scalar($path) ? (string) $path : get_debug_type($path)).'.');
            } elseif (! is_readable($path)) {
                $results[] = $this->error('paths', "Configured scan path [{$path}] is not readable.");
            }
        }

        return $results ?: [$this->pass('paths', count($paths).' source path(s) are readable.')];
    }

    /** @return list<DiagnosticResult> */
    private function tenant(): array
    {
        $results = [];
        $column = (string) $this->config->get('laravel-guard.tenant.column', 'tenant_id');
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column)) {
            $results[] = $this->error('tenant.column', "Tenant column [{$column}] is not a safe identifier.");
        }

        $resolver = $this->config->get('laravel-guard.tenant.resolver');
        if ($resolver !== null && (! is_string($resolver) || ! is_a($resolver, TenantResolver::class, true))) {
            $results[] = $this->error('tenant.resolver', 'The configured tenant resolver must implement '.TenantResolver::class.'.');
        }

        foreach ((array) $this->config->get('laravel-guard.tenant.models', []) as $model) {
            if (! is_string($model) || ! class_exists($model)) {
                $results[] = $this->error('tenant.models', 'A configured tenant model class does not exist: '.(is_scalar($model) ? (string) $model : get_debug_type($model)).'.');

                continue;
            }
            if (! is_a($model, TenantOwned::class, true) && ! in_array(GuardsTenant::class, class_uses_recursive($model), true)) {
                $results[] = $this->error('tenant.models', "Tenant model [{$model}] does not implement TenantOwned or use GuardsTenant.");
            }
        }

        return $results ?: [$this->pass('tenant', 'Tenant identifiers, resolver, and configured models are valid.')];
    }

    /** @return list<DiagnosticResult> */
    private function reporters(): array
    {
        $results = [];
        foreach ((array) $this->config->get('laravel-guard.reporters', []) as $format => $reporter) {
            if (! is_string($format) || ! is_string($reporter) || ! is_a($reporter, SecurityReporter::class, true)) {
                $results[] = $this->error('reporters', "Reporter [{$format}] must be a class implementing ".SecurityReporter::class.'.');

                continue;
            }
            try {
                $this->container->make($reporter);
            } catch (\Throwable $error) {
                $results[] = $this->error('reporters', "Reporter [{$reporter}] cannot be resolved: {$error->getMessage()}");
            }
        }

        return $results ?: [$this->pass('reporters', 'Configured custom reporters are resolvable.')];
    }

    /** @return list<DiagnosticResult> */
    private function integrations(): array
    {
        $results = [];
        foreach ($this->integrations->status() as $integration) {
            if ($integration['enabled'] && ! $integration['available']) {
                $results[] = $this->error('integrations.'.$integration['name'], "Integration [{$integration['name']}] is enabled but its package is unavailable.", 'Install the optional dependency or disable the integration.');
            }
        }

        return $results ?: [$this->pass('integrations', 'Enabled optional integrations are available.')];
    }

    /** @return list<DiagnosticResult> */
    private function uploads(): array
    {
        $allowed = $this->config->get('laravel-guard.uploads.allowed_detected_mimes', []);
        if (! is_array($allowed) || collect($allowed)->contains(fn ($mime) => ! is_string($mime) || ! str_contains($mime, '/'))) {
            return [$this->error('uploads.allowed_detected_mimes', 'Detected MIME allowlist entries must be MIME-type strings.')];
        }
        if ($allowed !== [] && ! class_exists(\finfo::class)) {
            return [$this->error('uploads.fileinfo', 'The fileinfo extension is required to enforce detected MIME allowlists.')];
        }

        return [$this->pass('uploads', 'Runtime MIME inspection dependencies and allowlists are valid.')];
    }

    /** @return list<DiagnosticResult> */
    private function baseline(): array
    {
        $path = $this->config->get('laravel-guard.baseline');
        if (! is_string($path) || trim($path) === '') {
            return [$this->error('baseline', 'The baseline path must be a non-empty string.')];
        }
        $directory = dirname($path);
        if (! is_dir($directory) || ! is_writable($directory)) {
            return [$this->error('baseline', "Baseline directory [{$directory}] must exist and be writable.")];
        }

        return [$this->pass('baseline', "Baseline path [{$path}] is writable.")];
    }

    /** @return list<DiagnosticResult> */
    private function runtime(): array
    {
        $enabled = (bool) $this->config->get('laravel-guard.runtime.enabled', false);
        $environments = (array) $this->config->get('laravel-guard.runtime.environments', ['local', 'testing']);
        if ($enabled && $this->app->environment('production') && ! in_array('production', $environments, true)) {
            return [$this->warning('runtime', 'Runtime Guard is enabled but production is not in runtime.environments.')];
        }
        if ($enabled && in_array('production', $environments, true)) {
            return [$this->warning('runtime', 'Runtime Guard is enabled in production.', 'Measure overhead and configure durable, redacted event handling before broad rollout.')];
        }

        return [$this->pass('runtime', 'Runtime Guard environment controls are internally consistent.')];
    }

    private function pass(string $check, string $message): DiagnosticResult
    {
        return new DiagnosticResult(DiagnosticStatus::Pass, $check, $message);
    }

    private function warning(string $check, string $message, ?string $remediation = null): DiagnosticResult
    {
        return new DiagnosticResult(DiagnosticStatus::Warning, $check, $message, $remediation);
    }

    private function error(string $check, string $message, ?string $remediation = null): DiagnosticResult
    {
        return new DiagnosticResult(DiagnosticStatus::Error, $check, $message, $remediation);
    }
}
