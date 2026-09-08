<?php

namespace LaravelGuard\Tests\Feature;

use LaravelGuard\Tests\TestCase;

final class BaselineGovernanceTest extends TestCase
{
    private string $baseline;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseline = sys_get_temp_dir().'/laravel-guard-baseline-'.bin2hex(random_bytes(5)).'.json';
        $this->app['config']->set('laravel-guard.baseline', $this->baseline);
    }

    protected function tearDown(): void
    {
        @unlink($this->baseline);
        parent::tearDown();
    }

    public function test_baseline_creation_requires_a_reason_for_findings(): void
    {
        $this->artisan('guard:baseline', ['--force' => true])->assertExitCode(2);
    }

    public function test_baseline_records_governance_and_supports_list_and_explain(): void
    {
        $this->artisan('guard:baseline', [
            '--force' => true,
            '--reason' => 'Reviewed fixture risk',
            '--owner' => 'security-team',
            '--expires' => '+30 days',
        ])->assertSuccessful();

        $data = json_decode(file_get_contents($this->baseline), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(4, $data['schema_version']);
        $this->assertSame('security-team', $data['findings'][0]['acceptance']['owner']);
        $this->assertSame('Reviewed fixture risk', $data['findings'][0]['acceptance']['reason']);
        $this->assertNotEmpty($data['findings'][0]['acceptance']['expires_at']);

        $rule = $data['findings'][0]['rule_id'];
        $this->artisan('guard:baseline', ['--list' => true])
            ->expectsOutputToContain('security-team')
            ->assertSuccessful();
        $this->artisan('guard:baseline', ['--explain' => $rule])
            ->expectsOutputToContain('Reviewed fixture risk')
            ->assertSuccessful();
    }

    public function test_baseline_policy_enforces_entry_and_approval_limits(): void
    {
        $this->app['config']->set('laravel-guard.baseline_governance.max_entries', 0);
        $this->artisan('guard:baseline', [
            '--force' => true,
            '--reason' => 'Reviewed fixture risk',
            '--expires' => '+30 days',
        ])->expectsOutputToContain('policy allows 0')->assertExitCode(2);

        $this->app['config']->set('laravel-guard.baseline_governance.max_entries', 500);
        $this->app['config']->set('laravel-guard.baseline_governance.required_approvals', 2);
        $this->artisan('guard:baseline', [
            '--force' => true,
            '--reason' => 'Reviewed fixture risk',
            '--owner' => 'service-owner',
            '--expires' => '+30 days',
        ])->expectsOutputToContain('policy requires 2')->assertExitCode(2);

        $this->artisan('guard:baseline', [
            '--force' => true,
            '--reason' => 'Reviewed fixture risk',
            '--owner' => 'service-owner',
            '--approver' => ['security-reviewer'],
            '--expires' => '+30 days',
        ])->assertSuccessful();

        $data = json_decode(file_get_contents($this->baseline), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(['security-reviewer'], $data['findings'][0]['acceptance']['approvers']);
    }

    public function test_prune_removes_resolved_and_expired_entries(): void
    {
        file_put_contents($this->baseline, json_encode([
            'schema_version' => 3,
            'generated_at' => date(DATE_ATOM),
            'fingerprints' => ['stale'],
            'findings' => [[
                'fingerprint' => 'stale', 'rule_id' => 'LG-TEST-001', 'severity' => 'low', 'title' => 'Stale',
                'acceptance' => ['owner' => 'team', 'reason' => 'reviewed', 'created_at' => date(DATE_ATOM), 'expires_at' => null],
            ]],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('guard:baseline', ['--prune' => true])
            ->expectsOutputToContain('Pruned 1')
            ->assertSuccessful();
        $data = json_decode(file_get_contents($this->baseline), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame([], $data['fingerprints']);
    }
}
