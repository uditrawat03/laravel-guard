<?php

namespace LaravelGuard\Tests\Feature;

use LaravelGuard\LaravelGuard;
use LaravelGuard\Tests\TestCase;

final class MalformedSuppressionTest extends TestCase
{
    public function test_a_malformed_ignore_value_does_not_crash_a_scan(): void
    {
        $this->app['config']->set('laravel-guard.ignore', 'invalid');

        $findings = $this->app->make(LaravelGuard::class)->scan('queries');

        $this->assertGreaterThan(0, $findings->count());
    }
}
