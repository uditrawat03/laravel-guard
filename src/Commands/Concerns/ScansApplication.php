<?php

namespace LaravelGuard\Commands\Concerns;

use Illuminate\Filesystem\Filesystem;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Reporting\ConsoleReporter;
use LaravelGuard\Core\Reporting\ReportManager;
use LaravelGuard\LaravelGuard;

trait ScansApplication
{
    protected function findings(): FindingCollection
    {
        $findings = $this->laravel->make(LaravelGuard::class)->scan($this->option('module'));
        $severity = $this->option('severity');

        return $severity ? $findings->atOrAbove(Severity::fromName($severity)) : $findings;
    }

    protected function report(FindingCollection $findings): void
    {
        $format = strtolower((string) $this->option('format'));
        if ($format === 'console') {
            $this->laravel->make(ConsoleReporter::class)->render($this, $findings);

            return;
        }
        $report = $this->laravel->make(ReportManager::class)->render($format, $findings);
        $output = $this->option('output');
        if ($output) {
            $this->laravel->make(Filesystem::class)->put($output, $report.PHP_EOL);
            $this->components->info("Security report written to {$output}.");
        } elseif ($report !== '') {
            $this->line($report);
        }
    }
}
