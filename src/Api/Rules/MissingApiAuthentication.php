<?php

namespace LaravelGuard\Api\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Routes\RouteAnalysis;

final class MissingApiAuthentication extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-API-001';
    }

    public function name(): string
    {
        return 'API route missing authentication';
    }

    public function category(): string
    {
        return 'api';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    public function scan(SecurityContext $context): iterable
    {
        $config = $context->config['routes'] ?? [];
        foreach ($context->app['router']->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/') || RouteAnalysis::public($route, $config)) {
                continue;
            }
            if (! RouteAnalysis::has(RouteAnalysis::middleware($route), ['auth', 'auth.basic', 'sanctum', 'passport'])) {
                yield SecurityFinding::fromRule($this, "API route {$route->uri()} has no recognized authentication middleware.", 'The endpoint may expose data or operations without an authenticated principal.', 'Require Sanctum, Passport, or another explicit API authentication guard.', metadata: RouteAnalysis::metadata($route));
            }
        }
    }
}
