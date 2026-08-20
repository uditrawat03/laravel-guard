<?php

namespace LaravelGuard\Tests\Feature;

use LaravelGuard\LaravelGuard;
use LaravelGuard\Tests\TestCase;

final class ConfigurationRulesTest extends TestCase
{
    public function test_production_debug_is_reported(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->app['config']->set('app.debug', true);
        $findings = $this->app->make(LaravelGuard::class)->scan('configuration');
        $this->assertContains('LG-CONFIG-001', array_map(fn ($f) => $f->ruleId, $findings->all()));
    }

    public function test_secure_configuration_has_no_configuration_findings(): void
    {
        $this->assertCount(0, $this->app->make(LaravelGuard::class)->scan('configuration'));
    }
}
