<?php

namespace LaravelGuard\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use LaravelGuard\Tests\TestCase;

final class UiDisabledTest extends TestCase
{
    public function test_dashboard_routes_and_local_fallback_gate_are_registered_during_package_discovery(): void
    {
        $this->assertTrue(Route::has('laravel-guard.ui.overview'));
        $this->assertTrue(Gate::has('viewLaravelGuard'));
    }
}
