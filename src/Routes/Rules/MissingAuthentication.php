<?php

namespace LaravelGuard\Routes\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Routes\RouteAnalysis;

final class MissingAuthentication extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-ROUTE-001';
    }

    public function name(): string
    {
        return 'Sensitive route missing authentication';
    }

    public function category(): string
    {
        return 'routes';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    public function scan(SecurityContext $x): iterable
    {
        $c = $x->config['routes'] ?? [];
        foreach ($x->app['router']->getRoutes() as $r) {
            if (RouteAnalysis::sensitive($r, $c) && ! RouteAnalysis::public($r, $c) && ! RouteAnalysis::has(RouteAnalysis::middleware($r), ['auth', 'auth.basic', 'sanctum'])) {
                yield SecurityFinding::fromRule($this, "{$r->uri()} has no recognized authentication middleware.", 'A sensitive action may be reachable anonymously.', 'Attach authentication middleware or explicitly allowlist this public route.', metadata: RouteAnalysis::metadata($r));
            }
        }
    }
}
