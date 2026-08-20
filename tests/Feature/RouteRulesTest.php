<?php

namespace LaravelGuard\Tests\Feature;

use Illuminate\Support\Facades\Route;
use LaravelGuard\LaravelGuard;
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
}
