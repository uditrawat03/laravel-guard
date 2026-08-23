<?php

namespace LaravelGuard\Tests\Feature;

use LaravelGuard\Tests\TestCase;

final class BaselineDoctorTest extends TestCase
{
    private string $baseline;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseline = sys_get_temp_dir().'/laravel-guard-doctor-'.bin2hex(random_bytes(5)).'.json';
        $this->app['config']->set('laravel-guard.baseline', $this->baseline);
    }

    protected function tearDown(): void
    {
        @unlink($this->baseline);
        parent::tearDown();
    }

    public function test_doctor_rejects_invalid_governance_configuration(): void
    {
        $this->app['config']->set('laravel-guard.baseline_governance.default_ttl_days', -1);

        $this->artisan('guard:doctor')
            ->expectsOutputToContain('non-negative integer')
            ->assertFailed();
    }

    public function test_doctor_rejects_malformed_baseline_json(): void
    {
        file_put_contents($this->baseline, '{not json');

        $this->artisan('guard:doctor')
            ->expectsOutputToContain('Baseline cannot be parsed')
            ->assertFailed();
    }

    public function test_doctor_warns_about_expired_entries(): void
    {
        file_put_contents($this->baseline, json_encode([
            'schema_version' => 3,
            'fingerprints' => ['expired'],
            'findings' => [[
                'fingerprint' => 'expired', 'rule_id' => 'LG-TEST-001', 'severity' => 'low', 'title' => 'Expired',
                'acceptance' => ['owner' => 'team', 'reason' => 'reviewed', 'created_at' => date(DATE_ATOM), 'expires_at' => '2020-01-01T00:00:00+00:00'],
            ]],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('guard:doctor')
            ->expectsOutputToContain('have expired')
            ->assertSuccessful();
        $this->artisan('guard:doctor', ['--strict' => true])->assertFailed();
    }
}
