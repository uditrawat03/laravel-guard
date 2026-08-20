<?php

namespace LaravelGuard\Testing;

use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\LaravelGuard;
use LaravelGuard\Routes\RouteAnalysis;
use LaravelGuard\Runtime\SecurityEventCollector;

trait LaravelGuardAssertions
{
    public function assertRouteRequiresAuthentication(string $name): void
    {
        $route = app('router')->getRoutes()->getByName($name);
        $this->assertNotNull($route, "Route [{$name}] does not exist.");
        $this->assertTrue(RouteAnalysis::has(RouteAnalysis::middleware($route), ['auth', 'auth.basic', 'sanctum']), "Route [{$name}] does not require recognized authentication.");
    }

    public function assertRouteRequiresAuthorization(string $name): void
    {
        $route = app('router')->getRoutes()->getByName($name);
        $this->assertNotNull($route, "Route [{$name}] does not exist.");
        $this->assertTrue(RouteAnalysis::hasAuthorizationMiddleware(RouteAnalysis::middleware($route)), "Route [{$name}] does not require recognized authorization.");
    }

    public function assertRouteUsesMiddleware(string $name, string $middleware): void
    {
        $route = app('router')->getRoutes()->getByName($name);
        $this->assertNotNull($route, "Route [{$name}] does not exist.");
        $this->assertContains($middleware, RouteAnalysis::middleware($route));
    }

    public function assertTenantSafe(callable $operation): void
    {
        $collector = app(SecurityEventCollector::class);
        $collector->clear();
        $operation();

        $events = $collector->all();
        $this->assertCount(0, $events, 'Laravel Guard observed '.count($events).' unsafe tenant operation(s).');
    }

    public function assertNoSecurityFindings(Severity $severity = Severity::High): void
    {
        $findings = app(LaravelGuard::class)->scan()->atOrAbove($severity);
        $this->assertCount(0, $findings, "Laravel Guard found {$findings->count()} finding(s) at or above {$severity->label()}.");
    }
}
