<?php

namespace LaravelGuard\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use LaravelGuard\Tests\TestCase;

final class DiagnosticsCommandsTest extends TestCase
{
    public function test_doctor_accepts_the_default_test_configuration(): void
    {
        $this->artisan('guard:doctor')
            ->expectsOutputToContain('Laravel Guard Configuration Doctor')
            ->expectsOutputToContain('source path(s) are readable')
            ->assertSuccessful();
    }

    public function test_doctor_validates_operational_drivers_and_runtime_paths(): void
    {
        $this->artisan('guard:doctor')
            ->expectsOutputToContain('Database driver')
            ->expectsOutputToContain('Cache driver')
            ->expectsOutputToContain('Queue driver')
            ->expectsOutputToContain('Filesystem driver')
            ->expectsOutputToContain('Scoped runtime state is registered')
            ->assertSuccessful();
    }

    public function test_doctor_can_run_explicit_connectivity_probes(): void
    {
        $this->app['config']->set('cache.default', 'array');
        $this->app['config']->set('queue.default', 'sync');

        $this->artisan('guard:doctor', ['--connectivity' => true])
            ->expectsOutputToContain('Database connectivity probe passed')
            ->expectsOutputToContain('Cache connectivity probe passed')
            ->expectsOutputToContain('Queue connectivity probe passed')
            ->expectsOutputToContain('Filesystem connectivity probe passed')
            ->assertSuccessful();
    }

    public function test_doctor_rejects_an_unknown_default_driver(): void
    {
        $this->app['config']->set('queue.default', 'missing-queue');

        $this->artisan('guard:doctor')
            ->expectsOutputToContain('Queue driver [missing-queue] is not configured')
            ->assertFailed();
    }

    public function test_doctor_reports_invalid_severity_and_path_configuration(): void
    {
        $this->app['config']->set('laravel-guard.minimum_severity', 'urgent');
        $this->app['config']->set('laravel-guard.paths', [base_path('missing-guard-source')]);

        $this->assertSame(1, Artisan::call('guard:doctor', ['--format' => 'json']));
        $output = Artisan::output();
        $this->assertStringContainsString('"status": "error"', $output);
        $this->assertStringContainsString('severity.minimum_severity', $output);
        $this->assertStringContainsString('missing-guard-source', $output);
    }

    public function test_explain_returns_actionable_rule_guidance(): void
    {
        $this->assertSame(0, Artisan::call('guard:explain', ['rule' => 'lg-tenant-002', '--format' => 'json']));
        $output = Artisan::output();
        $this->assertStringContainsString('"rule_id": "LG-TENANT-002"', $output);
        $this->assertStringContainsString('why_it_matters', $output);
        $this->assertStringContainsString('how_to_respond', $output);
        $this->assertStringContainsString('analysis_limits', $output);
        $this->assertStringContainsString('Cross-tenant model access', $output);
        $this->assertStringContainsString('Patient::findOrFail', $output);
        $this->assertStringContainsString("where('tenant_id'", $output);
        $this->assertStringContainsString('framework_versions', $output);
        $this->assertStringContainsString('false_positive_review', $output);
        $this->assertStringContainsString('suppression', $output);
    }

    public function test_explain_console_renders_code_examples(): void
    {
        $this->artisan('guard:explain', ['rule' => 'LG-QUERY-001'])
            ->expectsOutputToContain('Potentially vulnerable')
            ->expectsOutputToContain('DB::select')
            ->expectsOutputToContain('Safer pattern')
            ->expectsOutputToContain('Narrow suppression example')
            ->assertSuccessful();
    }

    public function test_explain_fails_for_an_unknown_rule(): void
    {
        $this->artisan('guard:explain', ['rule' => 'LG-NOT-REAL'])
            ->expectsOutputToContain('Unknown Laravel Guard rule')
            ->assertFailed();
    }
}
