<?php

namespace LaravelGuard\Routes\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Routes\RouteAnalysis;

final class MissingRateLimit extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-ROUTE-003';
    }

    public function name(): string
    {
        return 'Sensitive route missing rate limiting';
    }

    public function category(): string
    {
        return 'routes';
    }

    public function severity(): Severity
    {
        return Severity::Medium;
    }

    public function scan(SecurityContext $x): iterable
    {
        $c = $x->config['routes'] ?? [];
        foreach ($x->app['router']->getRoutes() as $r) {
            if (RouteAnalysis::sensitive($r, $c) && ! RouteAnalysis::public($r, $c) && ! RouteAnalysis::has(RouteAnalysis::middleware($r), ['throttle'])) {
                yield SecurityFinding::fromRule($this, "{$r->uri()} has no recognized throttle middleware.", 'Repeated requests may enable abuse or resource exhaustion.', 'Attach an appropriate throttle middleware.', Confidence::Medium, metadata: RouteAnalysis::metadata($r));
            }
        }
    }
}
