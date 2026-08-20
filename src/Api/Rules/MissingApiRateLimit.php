<?php

namespace LaravelGuard\Api\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Routes\RouteAnalysis;

final class MissingApiRateLimit extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-API-002';
    }

    public function name(): string
    {
        return 'API route missing rate limiting';
    }

    public function category(): string
    {
        return 'api';
    }

    public function severity(): Severity
    {
        return Severity::Medium;
    }

    public function scan(SecurityContext $context): iterable
    {
        $config = $context->config['routes'] ?? [];
        foreach ($context->app['router']->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/') || RouteAnalysis::public($route, $config)) {
                continue;
            }
            if (! RouteAnalysis::has(RouteAnalysis::middleware($route), ['throttle'])) {
                yield SecurityFinding::fromRule($this, "API route {$route->uri()} has no throttle middleware.", 'Clients can make unbounded requests and exhaust resources or automate abuse.', 'Apply a named limiter appropriate for the endpoint and authenticated principal.', Confidence::High, metadata: RouteAnalysis::metadata($route));
            }
        }
    }
}
