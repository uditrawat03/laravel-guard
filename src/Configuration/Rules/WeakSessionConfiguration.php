<?php

namespace LaravelGuard\Configuration\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;

final class WeakSessionConfiguration extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-CONFIG-002';
    }

    public function name(): string
    {
        return 'Weak session configuration';
    }

    public function category(): string
    {
        return 'configuration';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    public function scan(SecurityContext $context): iterable
    {
        $c = $context->app['config'];
        $issues = [];
        if ($c->get('session.http_only') !== true) {
            $issues[] = 'HttpOnly is disabled';
        } if ($context->app->environment('production') && $c->get('session.secure') !== true) {
            $issues[] = 'Secure cookies are disabled';
        } if (! in_array(strtolower((string) $c->get('session.same_site')), ['lax', 'strict'], true)) {
            $issues[] = 'SameSite is not lax or strict';
        } if ($issues) {
            yield SecurityFinding::fromRule($this, implode('; ', $issues).'.', 'Session cookies may be exposed or sent in unsafe cross-site contexts.', 'Enable secure, HttpOnly cookies and choose an appropriate SameSite policy.', metadata: ['issues' => $issues]);
        }
    }
}
