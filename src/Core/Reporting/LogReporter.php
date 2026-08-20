<?php

namespace LaravelGuard\Core\Reporting;

use LaravelGuard\Core\Contracts\SecurityReporter;
use LaravelGuard\Core\Findings\FindingCollection;
use Psr\Log\LoggerInterface;

final readonly class LogReporter implements SecurityReporter
{
    public function __construct(private LoggerInterface $logger) {}

    public function render(FindingCollection $findings): string
    {
        foreach ($findings as $finding) {
            $this->logger->warning('Laravel Guard: {rule} {title}', [
                'rule' => $finding->ruleId,
                'title' => $finding->title,
                'severity' => strtolower($finding->severity->name),
                'file' => $finding->location->file,
                'line' => $finding->location->line,
                'fingerprint' => $finding->fingerprint(),
            ]);
        }

        return '';
    }
}
