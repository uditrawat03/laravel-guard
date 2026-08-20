<?php

namespace LaravelGuard\Commands;

use Illuminate\Console\Command;
use LaravelGuard\LaravelGuard;

final class BenchmarkCommand extends Command
{
    protected $signature = 'guard:benchmark {--runs=5 : Number of scans} {--module= : Limit benchmark to a module}';

    protected $description = 'Measure Laravel Guard scan duration and peak memory';

    public function handle(LaravelGuard $guard): int
    {
        $runs = max(1, min(100, (int) $this->option('runs')));
        $durations = [];
        $findings = 0;
        for ($index = 0; $index < $runs; $index++) {
            $start = hrtime(true);
            $findings = $guard->scan($this->option('module'))->count();
            $durations[] = (hrtime(true) - $start) / 1_000_000;
        }
        sort($durations);
        $average = array_sum($durations) / count($durations);
        $p95 = $durations[(int) floor((count($durations) - 1) * .95)];
        $this->table(['Metric', 'Value'], [
            ['Runs', $runs], ['Findings', $findings], ['Average', number_format($average, 2).' ms'],
            ['P95', number_format($p95, 2).' ms'], ['Peak memory', number_format(memory_get_peak_usage(true) / 1024 / 1024, 2).' MB'],
        ]);

        return self::SUCCESS;
    }
}
