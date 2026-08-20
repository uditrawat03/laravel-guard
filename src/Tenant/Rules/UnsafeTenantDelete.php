<?php

namespace LaravelGuard\Tenant\Rules;

use LaravelGuard\Core\Findings\Severity;

final class UnsafeTenantDelete extends AbstractTenantEventRule
{
    public function id(): string
    {
        return 'LG-TENANT-005';
    }

    public function name(): string
    {
        return 'Unscoped tenant bulk delete';
    }

    public function severity(): Severity
    {
        return Severity::Critical;
    }

    protected function risk(): string
    {
        return 'Records across multiple tenants may be permanently deleted.';
    }

    protected function recommendation(): string
    {
        return 'Constrain the query by the active tenant column and require explicit authorization.';
    }
}
