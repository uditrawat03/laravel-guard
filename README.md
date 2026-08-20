# Laravel Guard

Laravel Guard is a Laravel-native security toolkit for static, configuration, route, and runtime tenant analysis. It explains risks and produces CI-ready reports without rewriting application code. A clean scan is useful evidence, not proof that an application is secure.

## Compatibility

- PHP 8.2+
- Laravel 10, 11, 12, and 13

## Installation

```bash
composer require --dev laravel-guard/laravel-guard
php artisan vendor:publish --tag=laravel-guard-config
php artisan guard:scan
```

Laravel package discovery registers the provider and commands automatically. PHP source is parsed with `nikic/php-parser`; application source is never executed by the scanner.

## Commands

```bash
php artisan guard:scan
php artisan guard:scan --module=routes --severity=high
php artisan guard:scan --format=sarif --output=guard.sarif
php artisan guard:check --fail-on=high
php artisan guard:diff main --fail-on=high
php artisan guard:baseline
php artisan guard:rules
php artisan guard:benchmark --runs=10
```

Report formats are `console`, `json`, `sarif`, `github`, `junit`, `html`, and `log`. `guard:check` exits with code 1 at the configured threshold. Baselines use normalized, symbol-aware fingerprints so line movement alone does not revive an accepted finding.

## Coverage

The default registry includes rules for:

- Tenant model constraints, missing context, cross-tenant access, bulk mutations, and raw queries
- Route authentication, authorization, throttling, administrative exposure, unsafe GET actions, and signed links
- Upload validation, executable formats, public storage, size limits, path traversal, and SVG handling
- Debug, session, CORS, key, filesystem, logging, proxy, database TLS, and mail configuration
- SQL interpolation, raw SQL, bulk update/delete, mass assignment, and sensitive model serialization
- Hardcoded and Git-tracked credentials, with secret values excluded from findings
- API authentication, throttling, and unsafe resource exposure

## Tenant Protection

Bind a resolver and apply the trait to tenant-owned models:

```php
use LaravelGuard\Tenant\Contracts\TenantResolver;
use LaravelGuard\Tenant\GuardsTenant;

$this->app->bind(TenantResolver::class, AppTenantResolver::class);

final class Project extends Model
{
    use GuardsTenant;
}
```

Configure the model class and table under `tenant.models` and `tenant.tables`. The trait applies a global scope, assigns the tenant key during creation, and blocks hydrated records carrying a different tenant key. Runtime DB inspection is disabled by default and should initially be enabled only in `local` and `testing`.

Adapters are included for `spatie/laravel-multitenancy` and `stancl/tenancy`; neither package is required.

## Suppressions

Prefer narrow suppressions with a documented reason:

```php
use LaravelGuard\Attributes\GuardIgnore;

#[GuardIgnore('LG-QUERY-002', reason: 'Static internal expression; no request input')]
final class MonthlyRollup {}
```

Configuration suppressions can target a route, file, symbol, or fingerprint. Scoped runtime exceptions use `LaravelGuard::allow($ruleId, $reason, $callback)` and never disable other rules globally.

## Testing

Add `LaravelGuard\Testing\LaravelGuardAssertions` to a PHPUnit test case, then use `assertNoSecurityFindings()`, `assertRouteRequiresAuthentication()`, `assertRouteRequiresAuthorization()`, or `assertTenantSafe()`.

## Custom Extensions

Implement `LaravelGuard\Core\Contracts\GuardRule` and list the class under `custom_rules`. Custom reporters implement `LaravelGuard\Core\Contracts\SecurityReporter` and are mapped by format name under `reporters`.

## CI

```yaml
- name: Laravel Guard
  run: php artisan guard:check --fail-on=high --format=github
```

See [implementation status](docs/ROADMAP.md), [security policy](SECURITY.md), and [contributing guide](CONTRIBUTING.md).

## License

MIT
