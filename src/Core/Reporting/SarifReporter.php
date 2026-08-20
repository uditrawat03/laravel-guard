<?php

namespace LaravelGuard\Core\Reporting;

use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Findings\Severity;

final class SarifReporter
{
    public function render(FindingCollection $findings): string
    {
        $rules = [];
        $results = [];
        foreach ($findings as $finding) {
            $rules[$finding->ruleId] = ['id' => $finding->ruleId, 'name' => $finding->title, 'shortDescription' => ['text' => $finding->description], 'help' => ['text' => $finding->recommendation]];
            $result = ['ruleId' => $finding->ruleId, 'level' => $this->level($finding->severity), 'message' => ['text' => $finding->description], 'fingerprints' => ['laravelGuard/v1' => $finding->fingerprint()]];
            if ($finding->location->file) {
                $result['locations'] = [['physicalLocation' => ['artifactLocation' => ['uri' => str_replace('\\', '/', $finding->location->file)], 'region' => ['startLine' => $finding->location->line ?? 1]]]];
            }
            $results[] = $result;
        }

        return json_encode(['version' => '2.1.0', '$schema' => 'https://json.schemastore.org/sarif-2.1.0.json', 'runs' => [['tool' => ['driver' => ['name' => 'Laravel Guard', 'informationUri' => 'https://github.com/laravel-guard/laravel-guard', 'rules' => array_values($rules)]], 'results' => $results]]], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function level(Severity $severity): string
    {
        return match ($severity) {
            Severity::Critical, Severity::High => 'error', Severity::Medium => 'warning', Severity::Low => 'note'
        };
    }
}
