<?php

namespace LaravelGuard\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelGuard\LaravelGuard;

final class BaselineCommand extends Command
{
    protected $signature = 'guard:baseline {--force : Replace an existing baseline}';

    protected $description = 'Save current findings as the Laravel Guard baseline';

    public function handle(Filesystem $files, LaravelGuard $guard): int
    {
        $path = config('laravel-guard.baseline');
        if ($files->exists($path) && ! $this->option('force')) {
            $this->components->error('Baseline already exists. Use --force to replace it.');

            return self::FAILURE;
        }$findings = $guard->scan();
        $files->put($path, json_encode(['generated_at' => date(DATE_ATOM), 'fingerprints' => array_map(fn ($f) => $f->fingerprint(), $findings->all())], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
        $this->components->info("Saved {$findings->count()} finding(s) to {$path}.");

        return self::SUCCESS;
    }
}
