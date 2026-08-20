<?php

namespace LaravelGuard\Commands;

use Illuminate\Console\Command;
use LaravelGuard\Commands\Concerns\ScansApplication;

final class ScanCommand extends Command
{
    use ScansApplication;

    protected $signature = 'guard:scan {--module= : Scan one module} {--severity= : Minimum displayed severity} {--format=console : console or json}';

    protected $description = 'Scan the application for Laravel security risks';

    public function handle(): int
    {
        $this->report($this->findings());

        return self::SUCCESS;
    }
}
