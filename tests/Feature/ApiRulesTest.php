<?php

namespace LaravelGuard\Tests\Feature;

use Illuminate\Support\Facades\Route;
use LaravelGuard\LaravelGuard;
use LaravelGuard\Tests\TestCase;

final class ApiRulesTest extends TestCase
{
    public function test_api_route_boundaries_are_enforced_without_flagging_public_or_web_routes(): void
    {
        Route::get('status', fn () => null);
        Route::get('api/open', fn () => null)->name('api.open');
        Route::get('api/public-health', fn () => null)->name('api.health');
        Route::get('api/protected', fn () => null)
            ->middleware(['auth:sanctum', 'throttle:api'])
            ->name('api.protected');
        config()->set('laravel-guard.routes.public', ['api/public-health']);

        $findings = $this->app->make(LaravelGuard::class)->scan('api')->all();
        $byRule = array_count_values(array_map(fn ($finding) => $finding->ruleId, $findings));

        $this->assertSame(1, $byRule['LG-API-001'] ?? 0);
        $this->assertSame(1, $byRule['LG-API-002'] ?? 0);

        foreach (array_filter($findings, fn ($finding) => str_starts_with($finding->ruleId, 'LG-API-00')) as $finding) {
            if ($finding->ruleId === 'LG-API-003') {
                continue;
            }

            $this->assertSame('api/open', $finding->metadata['uri']);
            $this->assertSame('api.open', $finding->metadata['name']);
            $this->assertStringContainsString('api/open', $finding->description);
        }
    }

    public function test_each_supported_authentication_middleware_prevents_authentication_finding(): void
    {
        foreach (['auth', 'auth.basic', 'sanctum', 'passport'] as $index => $middleware) {
            Route::get("api/auth-{$index}", fn () => null)
                ->middleware([$middleware, 'throttle:api']);
        }

        $ids = array_map(
            fn ($finding) => $finding->ruleId,
            $this->app->make(LaravelGuard::class)->scan('api')->all(),
        );

        $this->assertNotContains('LG-API-001', $ids);
        $this->assertNotContains('LG-API-002', $ids);
    }
}
