<?php

namespace LaravelGuard\Routes\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Routes\RouteAnalysis;

final class SensitiveGetAction extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-ROUTE-005';
    }

    public function name(): string
    {
        return 'Sensitive action uses GET';
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
        foreach ($context->app['router']->getRoutes() as $route) {
            $identity = strtolower($route->uri().' '.($route->getName() ?? '').' '.$route->getActionName());
            if (! in_array('GET', $route->methods(), true) || ! preg_match('/\b(delete|destroy|remove|purge|approve|disable|reset)\b/', $identity)) {
                continue;
            }
            yield SecurityFinding::fromRule($this, "GET {$route->uri()} appears to perform a state-changing action.", 'GET requests can be prefetched, cached, crawled, and triggered without CSRF protection.', 'Use POST, PATCH, PUT, or DELETE with CSRF and authorization protection.', Confidence::High, metadata: RouteAnalysis::metadata($route));
        }
    }
}
