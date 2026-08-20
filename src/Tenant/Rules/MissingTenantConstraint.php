<?php

namespace LaravelGuard\Tenant\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Tenant\Contracts\TenantOwned;

final class MissingTenantConstraint extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-TENANT-001';
    }

    public function name(): string
    {
        return 'Tenant model missing guard constraint';
    }

    public function category(): string
    {
        return 'tenant';
    }

    public function severity(): Severity
    {
        return Severity::Critical;
    }

    public function scan(SecurityContext $c): iterable
    {
        foreach ($c->config['tenant']['models'] ?? [] as $model) {
            if (! class_exists($model) || ! is_subclass_of($model, TenantOwned::class)) {
                yield SecurityFinding::fromRule($this, "Configured tenant model {$model} does not implement TenantOwned.", 'Queries may return records belonging to another tenant.', 'Implement TenantOwned and use the GuardsTenant trait.', Confidence::High, metadata: ['symbol' => $model]);
            }
        }
    }
}
