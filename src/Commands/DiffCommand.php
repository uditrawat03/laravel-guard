<?php

namespace LaravelGuard\Commands;

use Illuminate\Console\Command;
use LaravelGuard\Commands\Concerns\ScansApplication;
use LaravelGuard\Core\Diff\GitDiff;
use LaravelGuard\Core\Findings\Severity;

final class DiffCommand extends Command
{
    use ScansApplication;

    protected $signature = 'guard:diff {base=main : Git base reference} {--fail-on=high} {--module=} {--severity=} {--format=console} {--output=}';

    protected $description = 'Report findings introduced on changed lines relative to a Git reference';

    public function handle(): int
    {
        try {
            $diff = GitDiff::fromRef($this->argument('base'), base_path());
        } catch (\Throwable $error) {
            $this->components->error($error->getMessage());

            return self::FAILURE;
        }
        $findings = $diff->newFindings($this->findings());
        $this->report($findings);
        $threshold = Severity::fromName($this->option('fail-on'));

        return $findings->atOrAbove($threshold)->count() > 0 ? self::FAILURE : self::SUCCESS;
    }
}
