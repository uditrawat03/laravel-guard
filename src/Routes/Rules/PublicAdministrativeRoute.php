<?php

namespace LaravelGuard\Routes\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Routes\RouteAnalysis;

final class PublicAdministrativeRoute extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-ROUTE-004';
    }

    public function name(): string
    {
        return 'Public administrative route';
    }

    public function category(): string
    {
        return 'routes';
    }

    public function severity(): Severity
    {
        return Severity::Critical;
    }

    public function scan(SecurityContext $context): iterable
    {
        $config = $context->config['routes'] ?? [];
        foreach ($context->app['router']->getRoutes() as $route) {
            if (! preg_match('#(?:^|/)admin(?:/|$)#i', $route->uri()) || RouteAnalysis::public($route, $config)) {
                continue;
            }
            if (! RouteAnalysis::has(RouteAnalysis::middleware($route), ['auth', 'auth.basic', 'sanctum'])) {
                yield SecurityFinding::fromRule($this, "Administrative route {$route->uri()} is public.", 'Anonymous users may reach privileged administrative functionality.', 'Require authentication and explicit authorization for every administrative route.', metadata: RouteAnalysis::metadata($route));
            }
        }
    }
}
