<?php

namespace LaravelGuard\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelGuard\Commands\Concerns\ScansApplication;
use LaravelGuard\Core\Diff\GitBaseline;
use LaravelGuard\Core\Diff\GitDiff;
use LaravelGuard\Core\Diff\SecurityDiff;
use LaravelGuard\Core\Findings\Severity;

final class DiffCommand extends Command
{
    use ScansApplication;

    protected $signature = 'guard:diff {base=main : Git base reference} {--fail-on=high} {--module=} {--severity=} {--format=console} {--output=}';

    protected $description = 'Report findings introduced or resolved relative to a Git reference';

    public function handle(Filesystem $files): int
    {
        try {
            $base = $this->argument('base');
            $lines = GitDiff::fromRef($base, base_path());
            $baseline = GitBaseline::fromRef($base, base_path(), config('laravel-guard.baseline'));
            $diff = SecurityDiff::compare($this->findings(), $baseline, $lines);
        } catch (\Throwable $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }

        if (strtolower((string) $this->option('format')) === 'json') {
            $this->writeJson($files, $diff);
        } else {
            $this->report($diff->introduced);
            $this->renderResolved($diff);
        }
        $threshold = Severity::fromName($this->option('fail-on'));

        return $diff->introduced->atOrAbove($threshold)->count() > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function writeJson(Filesystem $files, SecurityDiff $diff): void
    {
        $report = json_encode($diff, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if ($output = $this->option('output')) {
            $files->put($output, $report.PHP_EOL);
            $this->info("Security diff written to {$output}.");
        } else {
            $this->line($report);
        }
    }

    private function renderResolved(SecurityDiff $diff): void
    {
        $this->newLine();
        $this->info(count($diff->resolved).' resolved finding(s).');
        if ($diff->resolved !== []) {
            $this->table(['Rule', 'Severity', 'Title'], array_map(fn (array $finding) => [
                $finding['rule_id'] ?? 'unknown', $finding['severity'] ?? 'unknown', $finding['title'] ?? 'Unknown finding',
            ], $diff->resolved));
        }
    }
}
