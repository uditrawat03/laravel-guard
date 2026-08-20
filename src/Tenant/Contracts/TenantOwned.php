<?php

namespace LaravelGuard\Tenant\Contracts;

interface TenantOwned
{
    public function tenantColumn(): string;
}
