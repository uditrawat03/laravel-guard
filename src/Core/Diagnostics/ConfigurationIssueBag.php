<?php

namespace LaravelGuard\Core\Diagnostics;

final class ConfigurationIssueBag
{
    /** @var list<DiagnosticResult> */
    private array $issues = [];

    public function add(DiagnosticResult $issue): void
    {
        $this->issues[] = $issue;
    }

    /** @return list<DiagnosticResult> */
    public function all(): array
    {
        return $this->issues;
    }

    public function hasErrors(): bool
    {
        return collect($this->issues)->contains(fn (DiagnosticResult $issue) => $issue->status === DiagnosticStatus::Error);
    }
}
