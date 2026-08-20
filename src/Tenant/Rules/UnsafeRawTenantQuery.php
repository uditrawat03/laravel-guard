<?php

namespace LaravelGuard\Tenant\Rules;

use LaravelGuard\Core\Findings\Severity;

final class UnsafeRawTenantQuery extends AbstractTenantEventRule
{
    public function id(): string
    {
        return 'LG-TENANT-006';
    }

    public function name(): string
    {
        return 'Unscoped raw tenant query';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    protected function risk(): string
    {
        return 'The query may read data from tenants other than the active tenant.';
    }

    protected function recommendation(): string
    {
        return 'Include a bound tenant constraint or use a tenant-scoped Eloquent model.';
    }
}
