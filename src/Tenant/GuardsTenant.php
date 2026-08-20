<?php

namespace LaravelGuard\Tenant;

use Illuminate\Database\Eloquent\Builder;
use LaravelGuard\Core\Exceptions\SecurityExceptionManager;
use LaravelGuard\Runtime\SecurityEventCollector;

trait GuardsTenant
{
    public static function bootGuardsTenant(): void
    {
        static::addGlobalScope('laravel-guard-tenant', function (Builder $builder): void {
            $model = $builder->getModel();
            $context = app(TenantContext::class);
            if ($context->active()) {
                $builder->where($model->qualifyColumn($model->tenantColumn()), $context->id());
            }
        });

        static::creating(function ($model): void {
            $context = app(TenantContext::class);
            if ($context->active() && $model->getAttribute($model->tenantColumn()) === null) {
                $model->setAttribute($model->tenantColumn(), $context->id());
            }
        });

        static::retrieved(function ($model): void {
            $context = app(TenantContext::class);
            $column = $model->tenantColumn();
            $actual = $model->getAttribute($column);
            if (! $context->active() || ! array_key_exists($column, $model->getAttributes()) || (string) $actual === (string) $context->id()) {
                return;
            }

            app(SecurityEventCollector::class)->record('LG-TENANT-002', 'A model belonging to another tenant was retrieved.', [
                'model' => $model::class,
                'current_tenant' => $context->id(),
                'requested_tenant' => $actual,
            ]);

            if (! app(SecurityExceptionManager::class)->allows('LG-TENANT-002')) {
                throw new CrossTenantAccessException($model::class, $context->id(), $actual);
            }
        });
    }

    public function tenantColumn(): string
    {
        return config('laravel-guard.tenant.column', 'tenant_id');
    }
}
