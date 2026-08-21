# PHPStan integration

Laravel Guard ships an optional PHPStan rule for tenant-owned models. Install PHPStan and include the package extension:

```neon
includes:
    - vendor/laravel-guard/laravel-guard/extension.neon

parameters:
    laravelGuard:
        tenantModels:
            - App\Models\OrganizationRecord
            - App\Models\Patient
```

Each listed class must implement `LaravelGuard\Tenant\Contracts\TenantOwned` or use `LaravelGuard\Tenant\GuardsTenant`. The extension is optional and PHPStan is not loaded during normal Laravel requests.
