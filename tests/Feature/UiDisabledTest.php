<?php

namespace LaravelGuard\Tests\Feature;

use Illuminate\Support\Facades\Route;
use LaravelGuard\Tests\TestCase;

final class UiDisabledTest extends TestCase
{
    public function test_dashboard_routes_are_not_registered_by_default(): void
    {
        $this->assertFalse(Route::has('laravel-guard.ui.overview'));
    }
}
