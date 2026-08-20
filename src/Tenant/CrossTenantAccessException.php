<?php

namespace LaravelGuard\Tenant;

use RuntimeException;

final class CrossTenantAccessException extends RuntimeException
{
    public function __construct(public readonly string $model, public readonly string|int $currentTenant, public readonly string|int|null $requestedTenant)
    {
        parent::__construct("Cross-tenant access blocked for {$model}.");
    }
}
