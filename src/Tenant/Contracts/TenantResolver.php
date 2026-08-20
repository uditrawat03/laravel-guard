<?php

namespace LaravelGuard\Tenant\Contracts;

interface TenantResolver
{
    public function currentTenantId(): string|int|null;
}
