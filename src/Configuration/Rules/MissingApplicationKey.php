<?php

namespace LaravelGuard\Configuration\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;

final class MissingApplicationKey extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-CONFIG-004';
    }

    public function name(): string
    {
        return 'Missing application encryption key';
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
        $key = $context->app['config']->get('app.key');
        if (! is_string($key) || trim($key) === '') {
            yield SecurityFinding::fromRule($this, 'The Laravel application key is empty.', 'Encrypted cookies, sessions, and application data cannot be protected correctly.', 'Generate a unique APP_KEY and store it in the deployment secret manager.');
        }
    }
}
