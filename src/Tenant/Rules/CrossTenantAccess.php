<?php

namespace LaravelGuard\Tenant\Rules;

use LaravelGuard\Core\Findings\Severity;

final class CrossTenantAccess extends AbstractTenantEventRule
{
    public function id(): string
    {
        return 'LG-TENANT-002';
    }

    public function name(): string
    {
        return 'Cross-tenant model access';
    }

    public function severity(): Severity
    {
        return Severity::Critical;
    }

    protected function risk(): string
    {
        return 'Data belonging to another tenant may be disclosed or modified.';
    }

    protected function recommendation(): string
    {
        return 'Preserve global tenant scopes and use a reasoned LaravelGuard::allow exception only for audited administration.';
    }
}
