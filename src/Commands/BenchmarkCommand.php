<?php

namespace LaravelGuard\Commands;

use Illuminate\Console\Command;
use LaravelGuard\Core\Support\OutputSchema;
use LaravelGuard\LaravelGuard;

final class BenchmarkCommand extends Command
{
    protected $signature = 'guard:benchmark {--runs=5 : Number of scans} {--module= : Limit benchmark to a module} {--path=* : Override source paths for this benchmark} {--format=console : console or json} {--max-p95-ms= : Fail when warm P95 exceeds this duration} {--max-memory-mb= : Fail when peak memory exceeds this amount}';

    protected $description = 'Measure Laravel Guard scan duration and peak memory';

    public function handle(LaravelGuard $guard): int
    {
        $runs = max(1, min(100, (int) $this->option('runs')));
        $durations = [];
        $format = strtolower((string) $this->option('format'));
        if (! in_array($format, ['console', 'json'], true)) {
            $this->error('The benchmark format must be console or json.');

            return self::INVALID;
        }

        $paths = array_values(array_filter(
            (array) $this->option('path'),
            static fn (mixed $path): bool => is_string($path) && trim($path) !== '',
        ));
        $findings = 0;
        for ($index = 0; $index < $runs; $index++) {
            $start = hrtime(true);
            memory_reset_peak_usage();
            $findings = $guard->scan($this->option('module'), $paths === [] ? null : $paths)->count();
            $durations[] = (hrtime(true) - $start) / 1_000_000;
        }
        $warmDurations = array_slice($durations, 1) ?: $durations;
        $metrics = [
            'schema' => OutputSchema::PERFORMANCE,
            'schema_version' => OutputSchema::PERFORMANCE_VERSION,
            'runs' => $runs,
            'findings' => $findings,
            'cold_ms' => round($durations[0], 3),
            'warm_average_ms' => round(array_sum($warmDurations) / count($warmDurations), 3),
            'warm_p95_ms' => round($this->percentile($warmDurations, .95), 3),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 3),
        ];
        $violations = $this->violations($metrics);
        $metrics['status'] = $violations === [] ? 'pass' : 'fail';
        $metrics['violations'] = $violations;

        if ($format === 'json') {
            $this->line((string) json_encode($metrics, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } else {
            $this->table(['Metric', 'Value'], [
                ['Runs', $runs], ['Findings', $findings], ['Cold', number_format($metrics['cold_ms'], 2).' ms'],
                ['Warm average', number_format($metrics['warm_average_ms'], 2).' ms'], ['Warm P95', number_format($metrics['warm_p95_ms'], 2).' ms'],
                ['Peak memory', number_format($metrics['peak_memory_mb'], 2).' MB'], ['Budget', strtoupper($metrics['status'])],
            ]);
            foreach ($violations as $violation) {
                $this->error($violation);
            }
        }

        return $violations === [] ? self::SUCCESS : self::FAILURE;
    }

    /** @param list<float> $values */
    private function percentile(array $values, float $percentile): float
    {
        sort($values);

        return $values[max(0, (int) ceil(count($values) * $percentile) - 1)];
    }

    /** @param array{warm_p95_ms: float, peak_memory_mb: float} $metrics */
    private function violations(array $metrics): array
    {
        $violations = [];
        $maximumP95 = $this->numericBudget('max-p95-ms');
        $maximumMemory = $this->numericBudget('max-memory-mb');
        if ($maximumP95 !== null && $metrics['warm_p95_ms'] > $maximumP95) {
            $violations[] = sprintf('Warm P95 %.3f ms exceeds the %.3f ms budget.', $metrics['warm_p95_ms'], $maximumP95);
        }
        if ($maximumMemory !== null && $metrics['peak_memory_mb'] > $maximumMemory) {
            $violations[] = sprintf('Peak memory %.3f MB exceeds the %.3f MB budget.', $metrics['peak_memory_mb'], $maximumMemory);
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
