<?php

namespace LaravelGuard\Tenant\Rules;

use LaravelGuard\Core\Findings\Severity;

final class UnsafeTenantUpdate extends AbstractTenantEventRule
{
    public function id(): string
    {
        return 'LG-TENANT-004';
    }

    public function name(): string
    {
        return 'Unscoped tenant bulk update';
    }

    public function severity(): Severity
    {
        return Severity::Critical;
    }

    protected function risk(): string
    {
        return 'Records across multiple tenants may be modified.';
    }

    protected function recommendation(): string
    {
        return 'Constrain the query by the active tenant column before updating.';
    }
}
