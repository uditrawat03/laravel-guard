<?php

namespace LaravelGuard\Tests\Feature;

use LaravelGuard\LaravelGuard;
use LaravelGuard\Tests\TestCase;

final class BaselineCheckTest extends TestCase
{
    private string $baseline;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseline = sys_get_temp_dir().'/laravel-guard-check-'.bin2hex(random_bytes(5)).'.json';
        $this->app['config']->set('laravel-guard.baseline', $this->baseline);
    }

    protected function tearDown(): void
    {
        @unlink($this->baseline);
        parent::tearDown();
    }

    public function test_expired_acceptance_no_longer_suppresses_a_finding(): void
    {
        $findings = $this->app->make(LaravelGuard::class)->scan('uploads')->all();
        $this->writeBaseline($findings, '2020-01-01T00:00:00+00:00');

        $this->artisan('guard:check', ['--module' => 'uploads', '--fail-on' => 'high'])
            ->expectsOutputToContain('expired baseline entries')
            ->assertFailed();
    }

    public function test_active_acceptance_suppresses_findings(): void
    {
        $findings = $this->app->make(LaravelGuard::class)->scan('uploads')->all();
        $this->writeBaseline($findings, '2099-01-01T00:00:00+00:00');

        $this->artisan('guard:check', ['--module' => 'uploads', '--fail-on' => 'high'])->assertSuccessful();
    }

    private function writeBaseline(array $findings, string $expiresAt): void
    {
        $entries = array_map(fn ($finding) => [
            'fingerprint' => $finding->fingerprint(),
            'rule_id' => $finding->ruleId,
            'severity' => strtolower($finding->severity->name),
            'title' => $finding->title,
            'acceptance' => ['owner' => 'security', 'reason' => 'reviewed', 'created_at' => date(DATE_ATOM), 'expires_at' => $expiresAt],
        ], $findings);
        file_put_contents($this->baseline, json_encode([
            'schema_version' => 3,
            'generated_at' => date(DATE_ATOM),
            'fingerprints' => array_column($entries, 'fingerprint'),
            'findings' => $entries,
        ], JSON_THROW_ON_ERROR));
    }
}
