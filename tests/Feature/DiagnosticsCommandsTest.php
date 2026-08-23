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
    }

    public function test_explain_fails_for_an_unknown_rule(): void
    {
        $this->artisan('guard:explain', ['rule' => 'LG-NOT-REAL'])
            ->expectsOutputToContain('Unknown Laravel Guard rule')
            ->assertFailed();
    }
}
