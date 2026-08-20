<?php

namespace LaravelGuard\Integrations\SpatieMultitenancy;

use LaravelGuard\Tenant\Contracts\TenantResolver;

final class SpatieTenantResolver implements TenantResolver
{
    public function currentTenantId(): string|int|null
    {
        $class = 'Spatie\\Multitenancy\\Models\\Tenant';
        if (! class_exists($class) || ! method_exists($class, 'current')) {
            return null;
        }
        $tenant = $class::current();

        return $tenant?->getKey();
    }
}
