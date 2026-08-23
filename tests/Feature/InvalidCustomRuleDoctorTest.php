<?php

namespace LaravelGuard\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use LaravelGuard\LaravelGuard;
use LaravelGuard\Tests\TestCase;

final class InvalidCustomRuleDoctorTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('laravel-guard.custom_rules', ['App\\Security\\MissingRule']);
    }

    public function test_doctor_starts_and_reports_a_custom_rule_boot_error(): void
    {
        $this->assertSame(1, Artisan::call('guard:doctor', ['--format' => 'json']));
        $output = Artisan::output();
        $this->assertStringContainsString('custom_rules', $output);
        $this->assertStringContainsString('MissingRule', $output);
    }

    public function test_scans_refuse_to_run_with_invalid_custom_rules(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('guard:doctor');

        $this->app->make(LaravelGuard::class)->scan();
    }
}
