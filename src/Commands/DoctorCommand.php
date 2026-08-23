<?php

namespace LaravelGuard\Commands;

use Illuminate\Console\Command;
use LaravelGuard\Core\Diagnostics\ConfigurationDoctor;
use LaravelGuard\Core\Diagnostics\DiagnosticStatus;

final class DoctorCommand extends Command
{
    protected $signature = 'guard:doctor
        {--format=console : console or json}
        {--strict : Fail for warnings as well as errors}
        {--output= : Validate a report output file destination}';

    protected $description = 'Validate Laravel Guard configuration and optional integrations';

    public function handle(ConfigurationDoctor $doctor): int
    {
        $format = strtolower((string) $this->option('format'));
        if (! in_array($format, ['console', 'json'], true)) {
            $this->error('Doctor format must be console or json.');

            return self::INVALID;
        }

        $output = $this->option('output');
        $results = $doctor->diagnose(is_string($output) ? $output : null);
        if ($format === 'json') {
            $this->line(json_encode(['diagnostics' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } else {
            $this->info('Laravel Guard Configuration Doctor');
            $this->table(['Status', 'Check', 'Result', 'Remediation'], array_map(fn ($result) => [
                strtoupper($result->status->value), $result->check, $result->message, $result->remediation ?? '',
            ], $results));
        }

        $errors = collect($results)->contains(fn ($result) => $result->status === DiagnosticStatus::Error);
        $warnings = collect($results)->contains(fn ($result) => $result->status === DiagnosticStatus::Warning);

        return $errors || ($warnings && $this->option('strict')) ? self::FAILURE : self::SUCCESS;
    }
}
