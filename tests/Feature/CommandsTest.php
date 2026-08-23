<?php

namespace LaravelGuard\Tests\Feature;

use LaravelGuard\Tests\TestCase;

final class CommandsTest extends TestCase
{
    public function test_rules_command_lists_registered_rules(): void
    {
        $this->artisan('guard:rules')->expectsOutputToContain('LG-CONFIG-001')->assertSuccessful();
    }

    public function test_console_scan_uses_public_command_output_api(): void
    {
        $this->artisan('guard:scan', ['--module' => 'uploads'])
            ->expectsOutputToContain('Laravel Guard Security Scan')
            ->assertSuccessful();
    }

    public function test_check_fails_at_configured_threshold(): void
    {
        $this->artisan('guard:check', ['--module' => 'uploads', '--fail-on' => 'high', '--format' => 'json', '--no-baseline' => true])->assertFailed();
    }

    public function test_benchmark_emits_machine_readable_metrics_and_accepts_a_budget(): void
    {
        $this->artisan('guard:benchmark', [
            '--runs' => 2,
            '--module' => 'uploads',
            '--format' => 'json',
            '--max-p95-ms' => 10000,
            '--max-memory-mb' => 1024,
        ])
            ->expectsOutputToContain('"schema":"laravel-guard/performance","schema_version":1')
            ->assertSuccessful();
    }

    public function test_benchmark_fails_when_a_performance_budget_is_exceeded(): void
    {
        $this->artisan('guard:benchmark', [
            '--runs' => 2,
            '--module' => 'uploads',
            '--max-memory-mb' => 0,
        ])
            ->expectsOutputToContain('exceeds the 0.000 MB budget')
            ->assertFailed();
    }
}
