<?php

namespace LaravelGuard\Routes\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Routes\AuthorizationInspector;
use LaravelGuard\Routes\RouteAnalysis;

final class MissingAuthorization extends AbstractGuardRule
{
    public function __construct(private readonly AuthorizationInspector $authorization) {}

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

    public function scan(SecurityContext $context): iterable
    {
        $config = $context->config['routes'] ?? [];
        foreach ($context->app['router']->getRoutes() as $route) {
            $middleware = RouteAnalysis::middleware($route);
            if (! RouteAnalysis::sensitive($route, $config) || RouteAnalysis::public($route, $config) || ! RouteAnalysis::has($middleware, ['auth', 'auth.basic', 'sanctum'])) {
                continue;
            }

            if (RouteAnalysis::has($middleware, $config['authorization_middleware'] ?? ['can']) || $this->authorization->controllerAuthorizes($route, $context)) {
                continue;
            }

            yield SecurityFinding::fromRule($this, "{$route->uri()} has authentication but no recognized route or controller authorization.", 'An authenticated user may act outside their permissions.', 'Add can/permission middleware or call a policy or gate in the controller.', Confidence::Medium, metadata: RouteAnalysis::metadata($route));
        }
    }
}
