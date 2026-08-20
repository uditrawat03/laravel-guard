<?php

namespace LaravelGuard\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelGuard\Commands\Concerns\ScansApplication;
use LaravelGuard\Core\Findings\Severity;

final class CheckCommand extends Command
{
    use ScansApplication;

    protected $signature = 'guard:check {--fail-on= : Severity threshold} {--module=} {--severity=} {--format=console} {--no-baseline : Ignore saved baseline}';

    protected $description = 'Run Laravel Guard and fail when the security threshold is reached';

    public function handle(Filesystem $files): int
    {
        $findings = $this->findings();
        $path = config('laravel-guard.baseline');
        if (! $this->option('no-baseline') && $path && $files->exists($path)) {
            $data = json_decode($files->get($path), true, flags: JSON_THROW_ON_ERROR);
            $findings = $findings->withoutFingerprints($data['fingerprints'] ?? []);
        }$this->report($findings);
        $threshold = Severity::fromName($this->option('fail-on') ?: config('laravel-guard.ci.fail_on', 'high'));

        return $findings->atOrAbove($threshold)->count() > 0 ? self::FAILURE : self::SUCCESS;
    }
}
