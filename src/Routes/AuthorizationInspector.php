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
            return false;
        }

        [$class, $method] = explode('@', $action, 2);
        $symbol = $class.'::'.$method;
        foreach ($this->sources->calls($context, ['authorize', 'authorizeForUser', 'allows', 'denies', 'check', 'inspect']) as $call) {
            if ($call->symbol === $symbol) {
                return true;
            }
        }

        return false;
    }
}
