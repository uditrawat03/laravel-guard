<?php

namespace LaravelGuard\Tenant;

use LaravelGuard\Tenant\Contracts\TenantResolver;

final class TenantContext
{
    private string|int|null $tenantId = null;

    private bool $resolved = false;

    private bool $resolving = false;

    public function __construct(private readonly ?TenantResolver $resolver) {}

    public function id(): string|int|null
    {
        if (($this->resolved && $this->tenantId !== null) || $this->resolving) {
            return $this->tenantId;
        }

        $this->resolving = true;
        try {
            $this->tenantId = $this->resolver?->currentTenantId();
            $this->resolved = true;
        } finally {
            $this->resolving = false;
        }

        return $this->tenantId;
    }

    public function active(): bool
    {
        return $this->id() !== null;
    }
}
