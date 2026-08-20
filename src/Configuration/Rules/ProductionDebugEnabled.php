<?php

namespace LaravelGuard\Configuration\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;

final class ProductionDebugEnabled extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-CONFIG-001';
    }

    public function name(): string
    {
        return 'Production debug mode enabled';
    }

    public function category(): string
    {
        return 'configuration';
    }

    public function severity(): Severity
    {
        return Severity::Critical;
    }

    public function scan(SecurityContext $context): iterable
    {
        if ($context->app->environment('production') && $context->app['config']->get('app.debug') === true) {
            yield SecurityFinding::fromRule($this, 'APP_DEBUG is enabled in production.', 'Exceptions may expose credentials, source code, and request data.', 'Set APP_DEBUG=false in production.');
        }
    }
}
