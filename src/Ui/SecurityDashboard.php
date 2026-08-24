<?php

namespace LaravelGuard\Ui;

use Composer\InstalledVersions;
use Illuminate\Contracts\Config\Repository;
use LaravelGuard\Core\Baseline\BaselineDocument;
use LaravelGuard\Core\Diagnostics\ConfigurationDoctor;
use LaravelGuard\Core\Rules\RuleRegistry;
use LaravelGuard\Core\Scoring\SecurityScore;
use LaravelGuard\LaravelGuard;
use LaravelGuard\Runtime\SecurityEventCollector;

final readonly class SecurityDashboard
{
    public function __construct(
        private LaravelGuard $guard,
        private ScanRunRepository $runs,
        private ConfigurationDoctor $doctor,
        private RuleRegistry $rules,
        private SecurityEventCollector $events,
        private Repository $config,
    ) {}

    public function run(string $trigger = 'web'): array
    {
        $started = hrtime(true);
        $findings = $this->guard->scan();
        $score = SecurityScore::fromFindings($findings);

        return $this->runs->save([
            'generated_at' => date(DATE_ATOM),
            'trigger' => $trigger,
            'package_version' => $this->version(),
            'duration_ms' => (int) round((hrtime(true) - $started) / 1_000_000),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1048576, 1),
            'score' => ['value' => $score->score, 'grade' => $score->grade, 'categories' => $score->categories],
            'counts' => $findings->counts(),
            'findings' => array_map(fn ($finding) => $this->finding($finding->jsonSerialize()), $findings->all()),
        ]);
    }

    public function latest(bool $scanWhenEmpty = true): ?array
    {
        $latest = $this->runs->latest();
        if ($latest === null && $scanWhenEmpty && (bool) $this->config->get('laravel-guard.ui.scan_on_first_view', false) && (bool) $this->config->get('laravel-guard.ui.allow_scan', false)) {
            return $this->run('first-view');
        }

        return $latest;
    }

    public function history(): array
    {
        return $this->runs->all();
    }

    public function diagnostics(): array
    {
        return array_map(fn ($result) => $this->redact($result->jsonSerialize()), $this->doctor->diagnose());
    }

    public function rules(): array
    {
        return array_map(fn ($rule) => [
            'id' => $rule->id(),
            'name' => $rule->name(),
            'description' => $rule->description(),
            'category' => $rule->category(),
            'severity' => strtolower($rule->severity()->name),
        ], $this->rules->all());
    }

    public function baseline(): array
    {
        $path = $this->config->get('laravel-guard.baseline');
        if (! is_string($path) || ! is_file($path)) {
            return ['exists' => false, 'entries' => [], 'expired' => 0, 'path' => $this->safePath(is_string($path) ? $path : null)];
        }

        try {
            $document = BaselineDocument::fromJson((string) file_get_contents($path));

            return [
                'exists' => true,
                'entries' => array_map(function ($entry): array {
                    $data = $entry->jsonSerialize();
                    $data['file'] = $this->safePath($data['file']);

                    return $this->redact($data);
                }, $document->entries),
                'expired' => count($document->expired()),
                'path' => $this->safePath($path),
                'generated_at' => $document->generatedAt,
            ];
        } catch (\Throwable $error) {
            return ['exists' => true, 'entries' => [], 'expired' => 0, 'path' => $this->safePath($path), 'error' => $this->redactText($error->getMessage())];
        }
    }

    public function runtime(): array
    {
        return [
            'enabled' => (bool) $this->config->get('laravel-guard.runtime.enabled', false),
            'environments' => (array) $this->config->get('laravel-guard.runtime.environments', []),
            'events' => array_map(fn ($event) => [
                'rule_id' => $event->ruleId,
                'message' => $this->redactText($event->message),
                'file' => $this->safePath($event->file),
                'line' => $event->line,
                'created_at' => $event->createdAt,
            ], $this->events->all()),
        ];
    }

    private function finding(array $finding): array
    {
        unset($finding['metadata']);
        $finding['file'] = $this->safePath($finding['file'] ?? null);

        return $this->redact($finding);
    }

    private function redact(array $value): array
    {
        array_walk_recursive($value, function (&$item): void {
            if (is_string($item)) {
                $item = $this->redactText($item);
            }
        });

        return $value;
    }

    private function redactText(string $value): string
    {
        $root = str_replace('\\', '/', base_path());
        $normalized = str_replace('\\', '/', $value);

        return str_replace([$root, str_replace('/', '\\', $root)], '[application]', $normalized);
    }

    private function safePath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        $path = str_replace('\\', '/', $path);
        $root = rtrim(str_replace('\\', '/', base_path()), '/').'/';

        return str_starts_with($path, $root) ? substr($path, strlen($root)) : basename($path);
    }

    public function version(): string
    {
        try {
            return InstalledVersions::getPrettyVersion('laravel-guard/laravel-guard') ?? 'dev';
        } catch (\Throwable) {
            return 'dev';
        }
    }
}
