<?php

namespace LaravelGuard\Tenant;

use Illuminate\Database\Eloquent\Builder;

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
            $actual = $model->getAttribute($model->tenantColumn());
            if ($context->active() && (string) $actual !== (string) $context->id()) {
                throw new CrossTenantAccessException($model::class, $context->id(), $actual);
            }
        });
    }

    public function tenantColumn(): string
    {
        return config('laravel-guard.tenant.column', 'tenant_id');
    }
}
