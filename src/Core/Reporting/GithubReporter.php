<?php

namespace LaravelGuard\Core\Reporting;

use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Findings\Severity;

final class GithubReporter
{
    public function render(FindingCollection $findings): string
    {
        $lines = [];
        foreach ($findings as $finding) {
            $type = in_array($finding->severity, [Severity::Critical, Severity::High], true) ? 'error' : 'warning';
            $properties = ['title' => "{$finding->ruleId} {$finding->title}"];
            if ($finding->location->file) {
                $properties['file'] = str_replace('\\', '/', $finding->location->file);
            }
            if ($finding->location->line) {
                $properties['line'] = (string) $finding->location->line;
            }
            $encoded = implode(',', array_map(fn ($key, $value) => $key.'='.$this->escape($value), array_keys($properties), $properties));
            $lines[] = "::{$type} {$encoded}::".$this->escape($finding->description.' '.$finding->recommendation);
        }

        return implode(PHP_EOL, $lines);
    }

    private function escape(string $value): string
    {
        return str_replace(['%', "\r", "\n", ':', ','], ['%25', '%0D', '%0A', '%3A', '%2C'], $value);
    }
}
