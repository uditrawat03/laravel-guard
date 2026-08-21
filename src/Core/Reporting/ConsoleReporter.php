<?php

namespace LaravelGuard\Core\Reporting;

use Illuminate\Console\Command;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Scoring\SecurityScore;

final class ConsoleReporter
{
    public function render(Command $command, FindingCollection $findings): void
    {
        $command->newLine();
        $command->info('Laravel Guard Security Scan');
        $counts = $findings->counts();
        $score = SecurityScore::fromFindings($findings);
        $command->table(['Severity', 'Findings'], [['CRITICAL', $counts['critical']], ['HIGH', $counts['high']], ['MEDIUM', $counts['medium']], ['LOW', $counts['low']]]);
        $command->line("Security score: <options=bold>{$score->score} / 100 ({$score->grade})</>");
        if ($findings->count() === 0) {
            $command->info('No security findings detected.');
            $command->comment('A clean scan is not proof of application security.');

            return;
        }
        foreach ($findings as $finding) {
            $command->newLine();
            $command->line("<options=bold>{$finding->severity->label()}  {$finding->ruleId}  {$finding->title}</>");
            $command->line($finding->description);
            if ($finding->location->file) {
                $command->line("Location: {$finding->location->file}".($finding->location->line ? ":{$finding->location->line}" : ''));
            }
            $command->line("Confidence: {$finding->confidence->label()}");
            $command->line("Recommendation: {$finding->recommendation}");
        }
        $command->newLine();
        $command->warn("{$findings->count()} finding(s) detected. A clean scan is not proof of application security.");
    }
}
