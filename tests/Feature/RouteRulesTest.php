<?php

namespace LaravelGuard\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use LaravelGuard\LaravelGuard;
use LaravelGuard\Tests\Fixtures\ResourceController;
use LaravelGuard\Tests\TestCase;

final class RouteRulesTest extends TestCase
{
    public function test_sensitive_public_route_is_reported(): void
    {
        Route::delete('admin/users/{user}', fn () => null)->name('admin.users.destroy');
        $ids = array_map(fn ($f) => $f->ruleId, $this->app->make(LaravelGuard::class)->scan('routes')->all());
        $this->assertContains('LG-ROUTE-001', $ids);
        $this->assertContains('LG-ROUTE-003', $ids);
    }

    public function test_protected_route_passes_recognized_checks(): void
    {
        Route::delete('admin/users/{user}', fn () => null)->middleware(['auth', 'can:delete,user', 'throttle:api']);
        $this->assertCount(0, $this->app->make(LaravelGuard::class)->scan('routes'));
    }

    public function test_authorize_resource_protects_all_controller_actions(): void
    {
        Route::delete('documents/{document}', [ResourceController::class, 'destroy'])->middleware(['auth', 'throttle:api']);
        $ids = array_map(fn ($finding) => $finding->ruleId, $this->app->make(LaravelGuard::class)->scan('routes')->all());

        $this->assertNotContains('LG-ROUTE-002', $ids);
    }

    public function test_configured_framework_route_is_ignored(): void
    {
        Route::put('framework/storage/{path}', fn () => null)->name('framework.storage.upload');
        $this->app['config']->set('laravel-guard.routes.ignore', ['storage.local.*', 'framework.*']);

        $this->assertCount(0, $this->app->make(LaravelGuard::class)->scan('routes'));
    }

    public function test_configured_model_without_policy_is_reported(): void
    {
        $this->app['config']->set('laravel-guard.routes.policy_models', [UnprotectedPolicyModel::class]);
        $ids = array_map(fn ($finding) => $finding->ruleId, $this->app->make(LaravelGuard::class)->scan('routes')->all());

        $this->assertContains('LG-ROUTE-007', $ids);
    }
}

final class UnprotectedPolicyModel extends Model {}
