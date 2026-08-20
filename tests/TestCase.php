<?php

namespace LaravelGuard\Tests;

use LaravelGuard\LaravelGuardServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LaravelGuardServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.debug', false);
        $app['config']->set('session.http_only', true);
        $app['config']->set('session.secure', true);
        $app['config']->set('session.same_site', 'lax');
        $app['config']->set('laravel-guard.paths', [__DIR__.'/Fixtures']);
        $app['config']->set('laravel-guard.exclude_paths', []);
    }
}
