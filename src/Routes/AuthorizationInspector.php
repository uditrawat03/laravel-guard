<?php

namespace LaravelGuard\Routes;

use Illuminate\Routing\Route;
use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Source\SourceIndex;

final readonly class AuthorizationInspector
{
    public function __construct(private SourceIndex $sources) {}

    public function controllerAuthorizes(Route $route, SecurityContext $context): bool
    {
        $action = $route->getActionName();
        if (! str_contains($action, '@')) {
            $uses = $route->getAction('controller') ?? $route->getAction('uses');
            $action = is_string($uses) ? $uses : $action;
        }
        if (! str_contains($action, '@')) {
            return false;
        }

        [$class, $method] = explode('@', $action, 2);
        $class = ltrim($class, '\\');
        $symbol = $class.'::'.$method;
        foreach ($this->sources->calls($context, ['authorize', 'authorizeForUser', 'allows', 'denies', 'check', 'inspect', 'authorizeResource']) as $call) {
            $callSymbol = ltrim((string) $call->symbol, '\\');
            if ($callSymbol === $symbol) {
                return true;
            }
            if ($call->name === 'authorizeResource' && str_starts_with($callSymbol, $class.'::')) {
                return true;
            }
        }

        return false;
    }
}
