<?php

namespace LaravelGuard\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Rules\RuleRegistry;
use LaravelGuard\Core\Support\OutputSchema;
use LaravelGuard\Runtime\SecurityEventCollector;
use LaravelGuard\Tenant\Contracts\TenantResolver;
use LaravelGuard\Tenant\TenantContext;
use LaravelGuard\Tenant\TenantQueryInspector;
use LaravelGuard\Uploads\Runtime\InspectUploadedFiles;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final class RuntimeBenchmarkCommand extends Command
{
    protected $signature = 'guard:benchmark-runtime {scenario : query, upload, or worker} {--runs=10 : Number of timed batches} {--operations=1000 : Operations per batch} {--format=console : console or json} {--max-p95-us= : Fail when batch P95 per operation exceeds this duration} {--max-memory-mb= : Fail when peak memory exceeds this amount} {--max-memory-growth-mb= : Fail when retained memory growth exceeds this amount}';

    protected $description = 'Measure Laravel Guard runtime query, upload, and long-running worker behavior';

    public function handle(DatabaseManager $database, InspectUploadedFiles $uploads, RuleRegistry $rules): int
    {
        $scenario = strtolower((string) $this->argument('scenario'));
        if (! in_array($scenario, ['query', 'upload', 'worker'], true)) {
            $this->error('The runtime benchmark scenario must be query, upload, or worker.');

            return self::INVALID;
        }

        $format = strtolower((string) $this->option('format'));
        if (! in_array($format, ['console', 'json'], true)) {
            $this->error('The benchmark format must be console or json.');

            return self::INVALID;
        }

        $runs = max(1, min(100, (int) $this->option('runs')));
        $operations = max(1, min(1_000_000, (int) $this->option('operations')));
        [$operation, $cleanup, $stateLeaks] = match ($scenario) {
            'query' => [...$this->queryOperation($database), static fn (): int => 0],
            'upload' => [...$this->uploadOperation($uploads), static fn (): int => 0],
            'worker' => $this->workerOperation($rules),
        };

        $durations = [];
        $peakMemory = 0.0;
        $memoryStart = memory_get_usage(true);
        try {
            for ($run = 0; $run < $runs; $run++) {
                memory_reset_peak_usage();
                $start = hrtime(true);
                for ($index = 0; $index < $operations; $index++) {
                    $operation();
                }
                $durations[] = ((hrtime(true) - $start) / 1_000) / $operations;
                $peakMemory = max($peakMemory, memory_get_peak_usage(true) / 1024 / 1024);
            }
        } finally {
            $cleanup();
        }
        $memoryGrowth = max(0, memory_get_usage(true) - $memoryStart) / 1024 / 1024;

        $metrics = [
            'schema' => OutputSchema::RUNTIME_PERFORMANCE,
            'schema_version' => OutputSchema::RUNTIME_PERFORMANCE_VERSION,
            'scenario' => $scenario,
            'runs' => $runs,
            'operations_per_run' => $operations,
            'average_us' => round(array_sum($durations) / count($durations), 3),
            'p95_us' => round($this->percentile($durations, .95), 3),
            'peak_memory_mb' => round($peakMemory, 3),
            'memory_growth_mb' => round($memoryGrowth, 3),
            'state_leaks' => $stateLeaks(),
        ];
        $violations = $this->violations($metrics);
        $metrics['status'] = $violations === [] ? 'pass' : 'fail';
        $metrics['violations'] = $violations;

        if ($format === 'json') {
            $this->line((string) json_encode($metrics, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } else {
            $this->table(['Metric', 'Value'], [
                ['Scenario', $scenario], ['Runs', $runs], ['Operations per run', $operations],
                ['Average', number_format($metrics['average_us'], 3).' us/op'],
                ['P95', number_format($metrics['p95_us'], 3).' us/op'],
                ['Peak memory', number_format($metrics['peak_memory_mb'], 3).' MB'],
                ['Memory growth', number_format($metrics['memory_growth_mb'], 3).' MB'],
                ['State leaks', $metrics['state_leaks']],
                ['Budget', strtoupper($metrics['status'])],
            ]);
            foreach ($violations as $violation) {
                $this->error($violation);
            }
        }

        return $violations === [] ? self::SUCCESS : self::FAILURE;
    }

    /** @return array{Closure(): void, Closure(): void} */
    private function queryOperation(DatabaseManager $database): array
    {
        $resolver = new class implements TenantResolver
        {
            public function currentTenantId(): string|int|null
            {
                return 'benchmark-tenant';
            }
        };
        $inspector = new TenantQueryInspector(new TenantContext($resolver), new SecurityEventCollector);
        $query = new QueryExecuted(
            'select * from "patients" where "tenant_id" = ?',
            ['benchmark-tenant'],
            0.0,
            $database->connection(),
        );
        $listener = static fn (QueryExecuted $event) => $inspector->inspect($event, ['patients'], 'tenant_id');

        return [
            static fn () => $listener($query),
            static fn () => null,
        ];
    }

    /** @return array{Closure(): void, Closure(): void} */
    private function uploadOperation(InspectUploadedFiles $middleware): array
    {
        $path = tempnam(sys_get_temp_dir(), 'laravel-guard-benchmark-');
        if ($path === false) {
            throw new \RuntimeException('Unable to create a temporary upload benchmark file.');
        }
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        file_put_contents($path, $png ?: '');
        $file = new UploadedFile($path, 'pixel.png', 'image/png', null, true);
        $request = Request::create('/uploads', 'POST', [], [], ['file' => $file]);
        $response = new Response('', Response::HTTP_NO_CONTENT);
        $next = static fn (): Response => $response;

        return [
            static fn () => $middleware->handle($request, $next),
            static function () use ($path): void {
                @unlink($path);
            },
        ];
    }

    /** @return array{Closure(): void, Closure(): void, Closure(): int} */
    private function workerOperation(RuleRegistry $rules): array
    {
        $rule = collect($rules->all())->first(fn ($rule) => $rule->id() === 'LG-TENANT-004');
        if ($rule === null) {
            throw new \RuntimeException('The worker benchmark requires the LG-TENANT-004 runtime rule.');
        }

        $leaks = 0;
        $cycle = 0;
        $operation = function () use ($rule, &$leaks, &$cycle): void {
            $events = $this->laravel->make(SecurityEventCollector::class);
            if ($events->all() !== []) {
                $leaks++;
            }

            $message = 'Worker scope cycle '.++$cycle;
            $events->record($rule->id(), $message);
            $findings = iterator_to_array($rule->scan($this->laravel->make(SecurityContext::class)));
            if (count($findings) !== 1 || $findings[0]->description !== $message) {
                $leaks++;
            }

            $this->laravel->forgetScopedInstances();
        };

        return [
            $operation,
            fn () => $this->laravel->forgetScopedInstances(),
            function () use (&$leaks): int {
                return $leaks;
            },
        ];
    }

    /** @param list<float> $values */
    private function percentile(array $values, float $percentile): float
    {
        sort($values);

        return $values[max(0, (int) ceil(count($values) * $percentile) - 1)];
    }

    /** @param array{p95_us: float, peak_memory_mb: float, memory_growth_mb: float, state_leaks: int} $metrics */
    private function violations(array $metrics): array
    {
        $violations = [];
        $maximumP95 = $this->numericBudget('max-p95-us');
        $maximumMemory = $this->numericBudget('max-memory-mb');
        $maximumMemoryGrowth = $this->numericBudget('max-memory-growth-mb');
        if ($maximumP95 !== null && $metrics['p95_us'] > $maximumP95) {
            $violations[] = sprintf('Runtime P95 %.3f us/op exceeds the %.3f us/op budget.', $metrics['p95_us'], $maximumP95);
        }
        if ($maximumMemory !== null && $metrics['peak_memory_mb'] > $maximumMemory) {
            $violations[] = sprintf('Peak memory %.3f MB exceeds the %.3f MB budget.', $metrics['peak_memory_mb'], $maximumMemory);
        }
        if ($maximumMemoryGrowth !== null && $metrics['memory_growth_mb'] > $maximumMemoryGrowth) {
            $violations[] = sprintf('Memory growth %.3f MB exceeds the %.3f MB budget.', $metrics['memory_growth_mb'], $maximumMemoryGrowth);
        }
        if ($metrics['state_leaks'] > 0) {
            $violations[] = sprintf('Detected %d retained-state violation(s) across worker scopes.', $metrics['state_leaks']);
        }

        return $violations;
    }

    private function numericBudget(string $option): ?float
    {
        $value = $this->option($option);
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (float) $value);
    }
}
