<?php

namespace LaravelGuard\Tests\Feature;

use LaravelGuard\LaravelGuard;
use LaravelGuard\Tests\TestCase;

final class AdvancedAnalysisTest extends TestCase
{
    public function test_ast_query_rule_detects_interpolation(): void
    {
        $this->app['config']->set('laravel-guard.paths', [__DIR__.'/../Fixtures/UnsafeQueries.php']);
        $findings = $this->app->make(LaravelGuard::class)->scan('queries');

        $this->assertContains('LG-QUERY-001', array_column(array_map(fn ($finding) => $finding->jsonSerialize(), $findings->all()), 'rule_id'));
    }

    public function test_guard_ignore_attribute_suppresses_only_its_rule(): void
    {
        $this->app['config']->set('laravel-guard.paths', [__DIR__.'/../Fixtures/SuppressedQuery.php']);
        $findings = $this->app->make(LaravelGuard::class)->scan('queries');

        $this->assertNotContains('LG-QUERY-001', array_map(fn ($finding) => $finding->ruleId, $findings->all()));
    }

    public function test_model_rules_understand_mass_assignment_and_exposure(): void
    {
        $this->app['config']->set('laravel-guard.paths', [__DIR__.'/../Fixtures/UnsafeModel.php']);
        $ids = array_map(fn ($finding) => $finding->ruleId, $this->app->make(LaravelGuard::class)->scan('models')->all());

        $this->assertContains('LG-MODEL-001', $ids);
        $this->assertContains('LG-MODEL-002', $ids);
    }

    public function test_secret_finding_never_serializes_the_complete_secret(): void
    {
        $this->app['config']->set('laravel-guard.paths', [__DIR__.'/../Fixtures/HardcodedCredential.php']);
        $findings = $this->app->make(LaravelGuard::class)->scan('secrets');
        $json = json_encode($findings, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $findings);
        $this->assertStringNotContainsString('sk_live_1234567890abcdefghijkl', $json);
        $this->assertStringContainsString('sk_l', $json);
    }

    public function test_scoped_exceptions_are_audited_and_unwound(): void
    {
        $guard = $this->app->make(LaravelGuard::class);
        $result = $guard->allow('LG-TENANT-002', 'Audited support operation', fn () => 'allowed');

        $this->assertSame('allowed', $result);
        $this->assertSame('LG-TENANT-002', $guard->exceptionAudit()[0]->rule);
        $this->assertSame('Audited support operation', $guard->exceptionAudit()[0]->reason);
    }
}
