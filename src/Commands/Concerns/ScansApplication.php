<?php

namespace LaravelGuard\Commands\Concerns;

use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Reporting\ConsoleReporter;
use LaravelGuard\Core\Reporting\JsonReporter;
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
        if ($this->option('format') === 'json') {
            $this->line($this->laravel->make(JsonReporter::class)->render($findings));
        } else {
            $this->laravel->make(ConsoleReporter::class)->render($this, $findings);
        }
    }
}
