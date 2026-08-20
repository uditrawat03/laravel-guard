<?php

namespace LaravelGuard\Integrations\StanclTenancy;

use LaravelGuard\Tenant\Contracts\TenantResolver;

final class StanclTenantResolver implements TenantResolver
{
    public function currentTenantId(): string|int|null
    {
        if (! function_exists('tenant')) {
            return null;
        }
        $tenant = tenant();

        return is_object($tenant) && method_exists($tenant, 'getTenantKey') ? $tenant->getTenantKey() : ($tenant->id ?? null);
    }
}
