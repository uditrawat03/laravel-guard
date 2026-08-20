<?php

namespace LaravelGuard\Core\Reporting;

use LaravelGuard\Core\Findings\FindingCollection;

final class JsonReporter
{
    public function render(FindingCollection $findings): string
    {
        return json_encode(['summary' => $findings->counts(), 'total' => $findings->count(), 'findings' => $findings], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
