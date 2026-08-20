<?php

namespace LaravelGuard\Tenant\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Tenant\Contracts\TenantOwned;
use LaravelGuard\Tenant\GuardsTenant;

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

    public function scan(SecurityContext $context): iterable
    {
        foreach ($context->config['tenant']['models'] ?? [] as $model) {
            $usesGuard = class_exists($model) && in_array(GuardsTenant::class, class_uses_recursive($model), true);
            $ownsTenant = class_exists($model) && is_subclass_of($model, TenantOwned::class);
            if (! $usesGuard && ! $ownsTenant) {
                yield SecurityFinding::fromRule($this, "Configured tenant model {$model} has no recognized tenant constraint.", 'Queries may return records belonging to another tenant.', 'Use the GuardsTenant trait or implement TenantOwned with an equivalent global scope.', Confidence::High, metadata: ['symbol' => $model]);
            }
        }
    }
}
