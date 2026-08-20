<?php

namespace LaravelGuard\Routes\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Routes\RouteAnalysis;

final class MissingAuthorization extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-ROUTE-002';
    }

    public function name(): string
    {
        return 'Sensitive route may be missing authorization';
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
            $m = RouteAnalysis::middleware($r);
            if (RouteAnalysis::sensitive($r, $c) && ! RouteAnalysis::public($r, $c) && RouteAnalysis::has($m, ['auth', 'auth.basic', 'sanctum']) && ! RouteAnalysis::has($m, $c['authorization_middleware'] ?? ['can'])) {
                yield SecurityFinding::fromRule($this, "{$r->uri()} has authentication but no recognized authorization middleware.", 'An authenticated user may act outside their permissions.', 'Add can/permission middleware or verify controller policy and gate checks.', Confidence::Medium, metadata: RouteAnalysis::metadata($r));
            }
        }
    }
}
