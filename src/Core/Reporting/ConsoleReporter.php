<?php

namespace LaravelGuard\Core\Reporting;

use Illuminate\Console\Command;
use LaravelGuard\Core\Findings\FindingCollection;

final class ConsoleReporter
{
    public function render(Command $command, FindingCollection $findings): void
    {
        $command->newLine();
        $command->components->info('Laravel Guard Security Scan');
        $c = $findings->counts();
        $command->table(['Severity', 'Findings'], [['CRITICAL', $c['critical']], ['HIGH', $c['high']], ['MEDIUM', $c['medium']], ['LOW', $c['low']]]);
        if ($findings->count() === 0) {
            $command->components->info('No security findings detected.');

            return;
        }foreach ($findings as $f) {
            $command->newLine();
            $command->line("<options=bold>{$f->severity->label()}  {$f->ruleId}  {$f->title}</>");
            $command->line($f->description);
            if ($f->location->file) {
                $command->line("Location: {$f->location->file}".($f->location->line ? ":{$f->location->line}" : ''));
            }$command->line("Confidence: {$f->confidence->label()}");
            $command->line("Recommendation: {$f->recommendation}");
        }$command->newLine();
        $command->warn("{$findings->count()} finding(s) detected. A clean scan is not proof of application security.");
    }
}
