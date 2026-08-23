<?php

namespace LaravelGuard\Core\Diagnostics;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use LaravelGuard\Core\Rules\RuleRegistry;

final readonly class OperationalDiagnostics
{
    public function __construct(
        private Application $app,
        private Repository $config,
        private RuleRegistry $rules,
    ) {}

    /** @return list<DiagnosticResult> */
    public function diagnose(?string $output = null): array
    {
        return [
            ...$this->suppressions(),
            ...$this->policyModels(),
            ...$this->git(),
            ...$this->output($output),
        ];
    }

    /** @return list<DiagnosticResult> */
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

        return [$this->pass('git', trim($version).' is available and the application is inside a worktree.')];
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
