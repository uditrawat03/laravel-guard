# Laravel Guard

Laravel Guard is a Laravel-native security toolkit that reports risky configuration, sensitive routes, unsafe uploads, and tenant-isolation mistakes during development and CI. It detects and explains risk; it does not modify application code or replace a security review.

## Compatibility

- PHP 8.2+
- Laravel 10, 11, 12, and 13

## Installation

```bash
composer require --dev laravel-guard/laravel-guard
php artisan vendor:publish --tag=laravel-guard-config
php artisan guard:scan
```

Laravel package discovery registers the provider and commands automatically.

## Commands

```bash
php artisan guard:scan
php artisan guard:scan --module=routes --severity=high
php artisan guard:scan --format=json
php artisan guard:check --fail-on=high
php artisan guard:baseline
php artisan guard:rules
```

`guard:check` returns exit code 1 when a finding meets the selected threshold. A baseline stores stable finding fingerprints so legacy applications can fail CI only for new findings.

## Tenant protection

Bind a resolver and opt tenant-owned models into the guard:

```php
use LaravelGuard\Tenant\Contracts\TenantOwned;
use LaravelGuard\Tenant\Contracts\TenantResolver;
use LaravelGuard\Tenant\GuardsTenant;

$this->app->bind(TenantResolver::class, AppTenantResolver::class);

final class Project extends Model implements TenantOwned
{
    use GuardsTenant;
}
```

The trait adds an active-tenant global scope, fills the tenant column on creation, and throws on a retrieved cross-tenant model. Configure tenant model classes under `tenant.models` to audit their opt-in status.

## Custom rules

Implement `LaravelGuard\Core\Contracts\GuardRule` and add the class to `custom_rules` in the published configuration, or register it at application boot with `LaravelGuard::registerRule(...)`.

## Suppression

Suppress a rule only for a specific file or symbol where possible:

```php
'ignore' => [
    'LG-ROUTE-002' => [
        ['target' => 'admin.reports.export', 'reason' => 'Controller policy is verified separately'],
    ],
],
```

Blanket suppression is supported with `true`, but should be reviewed carefully. Never treat a high score or clean scan as proof that an application is secure.

## CI

```yaml
- name: Laravel Guard
  run: php artisan guard:check --fail-on=high
```

## License

MIT
