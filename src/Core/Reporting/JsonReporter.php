<?php

namespace LaravelGuard\Core\Reporting;

use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Scoring\SecurityScore;
use LaravelGuard\Core\Support\OutputSchema;

final class JsonReporter
{
    public function render(FindingCollection $findings): string
    {
        return json_encode([
            'schema' => OutputSchema::REPORT,
            'schema_version' => OutputSchema::REPORT_VERSION,
            'summary' => $findings->counts(),
            'total' => $findings->count(),
            'score' => SecurityScore::fromFindings($findings),
            'findings' => $findings,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
