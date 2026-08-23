<?php

namespace LaravelGuard\Routes\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Routes\RouteAnalysis;

final class UnsignedSensitiveAction extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-ROUTE-006';
    }

    public function name(): string
    {
        return 'Sensitive link may require a signature';
    }

    public function category(): string
    {
        return 'routes';
    }

    public function severity(): Severity
    {
        return Severity::Medium;
    }

    public function scan(SecurityContext $context): iterable
    {
        $config = $context->config['routes'] ?? [];
        foreach ($context->app['router']->getRoutes() as $route) {
            if (RouteAnalysis::ignored($route, $config)) {
                continue;
            }
            $identity = strtolower($route->uri().' '.($route->getName() ?? ''));
            $isLink = array_intersect($route->methods(), ['GET', 'HEAD']) !== [];
            if (! $isLink || ! preg_match('/(verify|unsubscribe|invitation|signed-download|password\/reset)/', $identity)) {
                continue;
            }
            if (! RouteAnalysis::has(RouteAnalysis::middleware($route), ['signed'])) {
                yield SecurityFinding::fromRule($this, "{$route->uri()} has no recognized signed middleware.", 'A tampered or replayed link may authorize an unintended action.', 'Use temporary signed routes and validate expiry for sensitive links.', Confidence::Medium, metadata: RouteAnalysis::metadata($route));
            }
        }
    }
}
