<?php

namespace LaravelGuard\Commands;

use Illuminate\Console\Command;
use LaravelGuard\LaravelGuard;

final class ListRulesCommand extends Command
{
    protected $signature = 'guard:rules {--format=console}';

    protected $description = 'List registered Laravel Guard rules';

    public function handle(LaravelGuard $guard): int
    {
        $rows = array_map(fn ($r) => [$r->id(), $r->category(), $r->severity()->label(), $r->name()], $guard->rules());
        if ($this->option('format') === 'json') {
            $this->line(json_encode($rows, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } else {
            $this->table(['Rule', 'Module', 'Severity', 'Name'], $rows);
        }

        return self::SUCCESS;
    }
}
