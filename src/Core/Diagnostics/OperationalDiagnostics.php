<?php

namespace LaravelGuard\Core\Diagnostics;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use LaravelGuard\Core\Rules\RuleRegistry;
use LaravelGuard\Runtime\SecurityEventCollector;

final readonly class OperationalDiagnostics
{
    public function __construct(
        private Application $app,
        private Repository $config,
        private RuleRegistry $rules,
    ) {}

    /** @return list<DiagnosticResult> */
    public function diagnose(?string $output = null, bool $connectivity = false): array
    {
        return [
            ...$this->suppressions(),
            ...$this->policyModels(),
            ...$this->drivers(),
            ...$this->writablePaths(),
            ...$this->workerState(),
            ...$this->git(),
            ...($connectivity ? $this->connectivity() : []),
            ...$this->output($output),
        ];
    }

    /** @return list<DiagnosticResult> */
    private function drivers(): array
    {
        $definitions = [
            'database' => ['database.default', 'database.connections'],
            'cache' => ['cache.default', 'cache.stores'],
            'queue' => ['queue.default', 'queue.connections'],
            'filesystem' => ['filesystems.default', 'filesystems.disks'],
        ];
        $results = [];
        foreach ($definitions as $name => [$defaultKey, $collectionKey]) {
            $default = $this->config->get($defaultKey);
            $connections = $this->config->get($collectionKey);
            if (! is_string($default) || trim($default) === '') {
                $results[] = $this->error("drivers.{$name}", ucfirst($name).' default driver must be a non-empty string.');

                continue;
            }
            if (! is_array($connections) || ! array_key_exists($default, $connections)) {
                $results[] = $this->error("drivers.{$name}", ucfirst($name)." driver [{$default}] is not configured.");

                continue;
            }
            $results[] = $this->pass("drivers.{$name}", ucfirst($name)." driver [{$default}] is configured.");
        }

        return $results;
    }

    /** @return list<DiagnosticResult> */
    private function writablePaths(): array
    {
        $paths = ['storage' => $this->app->storagePath()];
        $uiPath = $this->config->get('laravel-guard.ui.storage_path');
        if (is_string($uiPath) && trim($uiPath) !== '') {
            $directory = dirname($uiPath);
            while (! is_dir($directory) && dirname($directory) !== $directory) {
                $directory = dirname($directory);
            }
            $paths['ui storage ancestor'] = $directory;
        }

        $results = [];
        foreach ($paths as $name => $path) {
            if (! is_dir($path) || ! is_writable($path)) {
                $results[] = $this->error('runtime.paths', ucfirst($name)." path [{$path}] must exist and be writable.");
            } else {
                $results[] = $this->pass('runtime.paths', ucfirst($name)." path [{$path}] is writable.");
            }
        }

        return $results;
    }

    /** @return list<DiagnosticResult> */
    private function workerState(): array
    {
        if (! $this->app->bound(SecurityEventCollector::class)) {
            return [$this->error('runtime.worker', 'The scoped security event collector is not registered.')];
        }

        return [$this->pass('runtime.worker', 'Scoped runtime state is registered; use guard:benchmark-runtime worker to enforce leak and retained-memory budgets.')];
    }

    /** @return list<DiagnosticResult> */
    private function connectivity(): array
    {
        $results = [];
        $probes = [
            'database' => function (): void {
                $this->app->make('db')->connection()->getPdo();
            },
            'cache' => function (): void {
                $key = 'laravel-guard:doctor:'.bin2hex(random_bytes(6));
                $cache = $this->app->make('cache')->store();
                $cache->put($key, true, 10);
                if ($cache->get($key) !== true) {
                    throw new \RuntimeException('Cache round trip failed.');
                }
                $cache->forget($key);
            },
            'queue' => function (): void {
                $this->app->make('queue')->connection()->size();
            },
            'filesystem' => function (): void {
                $path = 'laravel-guard-doctor/'.bin2hex(random_bytes(6)).'.tmp';
                $disk = $this->app->make('filesystem')->disk();
                if (! $disk->put($path, 'probe')) {
                    throw new \RuntimeException('Filesystem write failed.');
                }
                $disk->delete($path);
            },
        ];
        foreach ($probes as $name => $probe) {
            $binding = ['database' => 'db', 'cache' => 'cache', 'queue' => 'queue', 'filesystem' => 'filesystem'][$name];
            if (! $this->app->bound($binding)) {
                $results[] = $this->warning("connectivity.{$name}", ucfirst($name).' service is not bound; probe skipped.');

                continue;
            }
            try {
                $probe();
                $results[] = $this->pass("connectivity.{$name}", ucfirst($name).' connectivity probe passed.');
            } catch (\Throwable $error) {
                $results[] = $this->error("connectivity.{$name}", ucfirst($name).' connectivity probe failed ('.$error::class.').', 'Verify the driver endpoint and credentials; Laravel Guard intentionally redacts exception details.');
            }
        }

        return $results;
    }

    private function suppressions(): array
    {
        $ignore = $this->config->get('laravel-guard.ignore', []);
        if (! is_array($ignore)) {
            return [$this->error('suppressions', 'The ignore configuration must be an array keyed by rule ID.')];
        }

        $known = array_fill_keys(array_map(fn ($rule) => $rule->id(), $this->rules->all()), true);
        $results = [];
        foreach ($ignore as $ruleId => $targets) {
            if (! is_string($ruleId) || ! preg_match('/^LG-[A-Z0-9]+(?:-[A-Z0-9]+)+$/', $ruleId)) {
                $results[] = $this->error('suppressions.rule', 'Suppression keys must be Laravel Guard rule IDs such as LG-QUERY-001.');

                continue;
            }
            if (! isset($known[$ruleId])) {
                $results[] = $this->warning('suppressions.rule', "Suppression references unknown rule [{$ruleId}].", 'Remove stale suppressions or install the rule that owns this ID.');
            }
            if ($targets === true || $targets === ['*']) {
                $results[] = $this->warning('suppressions.scope', "Suppression [{$ruleId}] disables the rule globally.", 'Prefer a file, symbol, route, or fingerprint target.');

                continue;
            }
            if (! is_string($targets) && ! is_array($targets)) {
                $results[] = $this->error('suppressions.targets', "Suppression [{$ruleId}] must contain true, a target string, or a target list.");

                continue;
            }
            foreach ((array) $targets as $target) {
                if (is_string($target) && trim($target) !== '') {
                    continue;
                }
                if (is_array($target) && is_string($target['target'] ?? null) && trim($target['target']) !== '') {
                    if (! is_string($target['reason'] ?? null) || trim($target['reason']) === '') {
                        $results[] = $this->warning('suppressions.reason', "Structured suppression [{$ruleId}] has no documented reason.");
                    }

                    continue;
                }
                $results[] = $this->error('suppressions.targets', "Suppression [{$ruleId}] contains an invalid target.");
            }
        }

        return $results ?: [$this->pass('suppressions', count($ignore).' configured suppression rule(s) have a valid structure.')];
    }

    /** @return list<DiagnosticResult> */
    private function policyModels(): array
    {
        $models = $this->config->get('laravel-guard.routes.policy_models', []);
        if (! is_array($models)) {
            return [$this->error('routes.policy_models', 'Policy models must be configured as an array of Eloquent model class names.')];
        }

        $results = [];
        foreach ($models as $model) {
            if (! is_string($model) || ! class_exists($model)) {
                $results[] = $this->error('routes.policy_models', 'A configured policy model does not exist: '.(is_scalar($model) ? (string) $model : get_debug_type($model)).'.');
            } elseif (! is_a($model, Model::class, true)) {
                $results[] = $this->error('routes.policy_models', "Configured policy subject [{$model}] is not an Eloquent model.");
            }
        }

        return $results ?: [$this->pass('routes.policy_models', count($models).' configured policy model(s) are valid.')];
    }

    /** @return list<DiagnosticResult> */
    private function git(): array
    {
        if (! is_callable('proc_open')) {
            return [$this->warning('git.executable', 'PHP cannot start Git because proc_open is unavailable.', 'Enable proc_open to use guard:diff.')];
        }

        [$status, $version] = $this->run(['git', '--version']);
        if ($status !== 0) {
            return [$this->warning('git.executable', 'Git is unavailable to Laravel Guard.', 'Install Git and ensure it is present on PATH to use guard:diff.')];
        }

        [$repositoryStatus, $inside] = $this->run(['git', '-C', $this->app->basePath(), 'rev-parse', '--is-inside-work-tree']);
        if ($repositoryStatus !== 0 || trim($inside) !== 'true') {
            return [$this->warning('git.repository', "Application path [{$this->app->basePath()}] is not inside a Git worktree.", 'Run guard:diff from a Git checkout with the required history.')];
        }

        $results = [$this->pass('git', trim($version).' is available and the application is inside a worktree.')];
        [$shallowStatus, $shallow] = $this->run(['git', '-C', $this->app->basePath(), 'rev-parse', '--is-shallow-repository']);
        if ($shallowStatus === 0 && trim($shallow) === 'true') {
            $results[] = $this->warning('git.history', 'The application is a shallow Git checkout.', 'Fetch the comparison ref and sufficient history before running guard:diff.');
        }
        [$countStatus, $count] = $this->run(['git', '-C', $this->app->basePath(), 'rev-list', '--count', 'HEAD']);
        if ($countStatus === 0 && (int) trim($count) < 2) {
            $results[] = $this->warning('git.history', 'The checkout has fewer than two commits.', 'Fetch history before comparing introduced and resolved findings.');
        }

        return $results;
    }

    /** @return list<DiagnosticResult> */
    private function output(?string $path): array
    {
        if ($path === null || trim($path) === '') {
            return [];
        }
        if (is_dir($path)) {
            return [$this->error('output', "Output path [{$path}] is a directory.", 'Provide a report filename, not a directory.')];
        }
        if (file_exists($path) && ! is_writable($path)) {
            return [$this->error('output', "Existing output file [{$path}] is not writable.")];
        }
        $directory = dirname($path);
        if (! is_dir($directory) || ! is_writable($directory)) {
            return [$this->error('output', "Output directory [{$directory}] must exist and be writable.")];
        }

        return [$this->pass('output', "Report output path [{$path}] is writable.")];
    }

    /** @return array{0:int, 1:string} */
    private function run(array $command): array
    {
        $process = @proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($process)) {
            return [1, ''];
        }
        $output = stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), (string) $output];
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
