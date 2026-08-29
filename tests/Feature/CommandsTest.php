<?php

namespace LaravelGuard\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
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

    public function test_benchmark_can_isolate_explicit_source_paths(): void
    {
        $this->artisan('guard:benchmark', [
            '--runs' => 1,
            '--module' => 'uploads',
            '--path' => [__DIR__.'/../Fixtures/UnsafeQueries.php'],
            '--format' => 'json',
        ])
            ->expectsOutputToContain('"findings":0')
            ->assertSuccessful();
    }

    public function test_runtime_benchmark_emits_machine_readable_query_metrics(): void
    {
        $this->artisan('guard:benchmark-runtime', ['scenario' => 'query', '--runs' => 2, '--operations' => 2, '--format' => 'json'])
            ->expectsOutputToContain('"schema":"laravel-guard/runtime-performance","schema_version":2,"scenario":"query"')
            ->assertSuccessful();
    }

    public function test_runtime_benchmark_detects_clean_worker_scopes(): void
    {
        $exitCode = Artisan::call('guard:benchmark-runtime', [
            'scenario' => 'worker',
            '--runs' => 2,
            '--operations' => 3,
            '--format' => 'json',
            '--max-memory-growth-mb' => 16,
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('"schema_version":2,"scenario":"worker"', $output);
        $this->assertStringContainsString('"state_leaks":0', $output);
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

    public function test_runtime_benchmark_fails_when_an_overhead_budget_is_exceeded(): void
    {
        $this->artisan('guard:benchmark-runtime', [
            'scenario' => 'upload',
            '--runs' => 2,
            '--operations' => 2,
            '--max-p95-us' => 0,
        ])
            ->expectsOutputToContain('exceeds the 0.000 us/op budget')
            ->assertFailed();
    }
}
