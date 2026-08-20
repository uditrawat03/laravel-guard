<?php

namespace LaravelGuard\Tenant;

use LaravelGuard\Tenant\Contracts\TenantResolver;

final readonly class TenantContext
{
    public function __construct(private ?TenantResolver $resolver) {}

    public function id(): string|int|null
    {
        return $this->resolver?->currentTenantId();
    }

    public function active(): bool
    {
        return $this->id() !== null;
    }
}
