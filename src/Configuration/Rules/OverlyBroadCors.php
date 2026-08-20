<?php

namespace LaravelGuard\Configuration\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;

final class OverlyBroadCors extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-CONFIG-003';
    }

    public function name(): string
    {
        return 'Overly broad CORS policy';
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
        $c = $context->app['config']->get('cors', []);
        if (in_array('*', $c['allowed_origins'] ?? [], true) && ($c['supports_credentials'] ?? false)) {
            yield SecurityFinding::fromRule($this, 'CORS allows every origin while credentials are enabled.', 'Untrusted origins may make authenticated cross-origin requests.', 'Restrict allowed_origins to explicitly trusted domains.');
        }
    }
}
