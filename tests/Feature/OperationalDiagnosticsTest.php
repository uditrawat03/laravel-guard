<?php

namespace LaravelGuard\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\LaravelGuard;
use LaravelGuard\Tests\TestCase;

final class OperationalDiagnosticsTest extends TestCase
{
    public function test_doctor_rejects_invalid_suppression_structures(): void
    {
        $this->app['config']->set('laravel-guard.ignore', ['not-a-rule' => false]);

        $this->artisan('guard:doctor')
            ->expectsOutputToContain('Suppression keys must be Laravel Guard rule IDs')
            ->assertFailed();
    }

    public function test_doctor_warns_for_unknown_and_global_suppressions(): void
    {
        $this->app['config']->set('laravel-guard.ignore', [
            'LG-UNKNOWN-999' => ['app/Legacy.php'],
            'LG-QUERY-001' => true,
        ]);

        $this->artisan('guard:doctor')
            ->expectsOutputToContain('unknown rule [LG-UNKNOWN-999]')
            ->expectsOutputToContain('disables the rule globally')
            ->assertSuccessful();
        $this->artisan('guard:doctor', ['--strict' => true])->assertFailed();
    }

    public function test_doctor_rejects_invalid_policy_model_configuration(): void
    {
        $this->app['config']->set('laravel-guard.routes.policy_models', ['Missing\\ClinicalRecord']);

        $this->artisan('guard:doctor')
            ->expectsOutputToContain('configured policy model does not exist')
            ->assertFailed();
    }

    public function test_doctor_validates_an_explicit_report_destination(): void
    {
        $valid = sys_get_temp_dir().'/laravel-guard-report-'.bin2hex(random_bytes(4)).'.json';
        $missing = sys_get_temp_dir().'/missing-'.bin2hex(random_bytes(4)).'/guard.json';

        $this->artisan('guard:doctor', ['--output' => $valid])
            ->expectsOutputToContain('Report output path')
            ->assertSuccessful();
        $this->artisan('guard:doctor', ['--output' => $missing])
            ->expectsOutputToContain('must exist and be writable')
            ->assertFailed();
    }

    public function test_doctor_rejects_an_unknown_output_format(): void
    {
        $this->artisan('guard:doctor', ['--format' => 'yaml'])
            ->expectsOutputToContain('must be console or json')
            ->assertExitCode(2);
    }

    public function test_fingerprint_configuration_suppression_matches_the_documented_behavior(): void
    {
        $guard = $this->app->make(LaravelGuard::class);
        $initial = $guard->scan('queries');
        $finding = $initial->all()[0];
        $this->app['config']->set('laravel-guard.ignore', [
            $finding->ruleId => [[
                'target' => $finding->fingerprint(),
                'reason' => 'Reviewed generated query fixture.',
            ]],
        ]);
        $this->app->forgetInstance(LaravelGuard::class);
        $this->app->forgetInstance(SecurityContext::class);
        $guard = $this->app->make(LaravelGuard::class);

        $rescanned = $guard->scan('queries');

        $this->assertCount($initial->count() - 1, $rescanned);
        $this->assertNotContains($finding->fingerprint(), array_map(fn ($item) => $item->fingerprint(), $rescanned->all()));
    }

    public function test_doctor_reports_git_capability(): void
    {
        $this->assertSame(0, Artisan::call('guard:doctor', ['--format' => 'json']));
        $this->assertStringContainsString('"check": "git"', Artisan::output());
    }
}
