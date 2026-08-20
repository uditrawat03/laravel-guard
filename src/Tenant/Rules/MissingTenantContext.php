<?php

namespace LaravelGuard\Tenant\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;

final class MissingTenantContext extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-TENANT-003';
    }

    public function name(): string
    {
        return 'Tenant resolver is not configured';
    }

    public function category(): string
    {
        return 'tenant';
    }

    public function severity(): Severity
    {
        return Severity::Critical;
    }

    public function scan(SecurityContext $context): iterable
    {
        if (($context->config['tenant']['models'] ?? []) !== [] && empty($context->config['tenant']['resolver'])) {
            yield SecurityFinding::fromRule($this, 'Tenant models are configured but no TenantResolver binding is configured.', 'Tenant scopes cannot identify the active tenant.', 'Set laravel-guard.tenant.resolver to a TenantResolver implementation.');
        }
    }
}
