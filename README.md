# Laravel Guard

Laravel Guard finds Laravel-specific security mistakes before they become incidents. It combines source analysis, route and configuration inspection, CI reporting, and opt-in runtime enforcement without executing application source during a scan.

A clean scan is useful evidence, not proof that an application is secure. Laravel Guard complements code review, dependency scanning, dynamic testing, infrastructure hardening, and penetration testing.

## Why use Laravel Guard?

- **Catch framework-specific risks early.** Detect missing route authorization, unsafe raw queries, weak upload validation, exposed secrets, insecure production configuration, and tenant isolation gaps in one scan.
- **Protect multi-tenant data twice.** Static rules flag suspicious access patterns while the optional Eloquent trait scopes queries, assigns tenant keys, and blocks cross-tenant hydration at runtime.
- **Give developers actionable findings.** Stable rule IDs, severity, confidence, source locations, remediation guidance, and documentation links make results suitable for code review rather than producing an unexplained pass/fail signal.
- **Adopt without stopping delivery.** Baselines suppress known debt by fingerprint, while `guard:diff` reports both introduced and resolved findings so CI can focus on the current change.
- **Use the same evidence everywhere.** Console output helps locally; SARIF, GitHub annotations, JUnit, JSON, HTML, and logs fit existing CI and audit workflows.
- **Keep sensitive data private.** Secret findings report locations and metadata without copying secret values. Upload inspection records hashes and MIME metadata, never file contents.
- **Avoid ecosystem lock-in.** The core has no required tenancy, permission, Telescope, Debugbar, or PHPStan dependency. Adapters activate only when their package and configuration are present.

## Compatibility

- PHP 8.2+
- Laravel 10, 11, 12, and 13

The CI matrix tests supported framework generations independently. See the [backwards-compatibility promise](docs/BACKWARDS-COMPATIBILITY.md).

## Installation

```bash
composer require --dev laravel-guard/laravel-guard
php artisan vendor:publish --tag=laravel-guard-config
php artisan guard:scan
```

Laravel package discovery registers the provider and commands automatically. Application PHP is parsed with `nikic/php-parser`; it is not included or executed by the scanner.

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

`guard:check` exits with code 1 at the configured threshold. `guard:diff` compares the current scan with the baseline stored at a Git ref and reports introduced and resolved findings. Baselines use normalized, symbol-aware fingerprints, so moving a finding to another line does not revive accepted debt.

## Security Coverage

- Tenant model constraints, missing context, cross-tenant access, bulk mutations, and raw queries
- Route authentication, authorization and policy registration, throttling, administrative exposure, unsafe GET actions, and signed links
- Upload validation, executable formats, MIME signatures, public storage, size limits, path traversal, and SVG handling
- Debug, session, CORS, key, filesystem, logging, proxy, database TLS, and mail configuration
- SQL interpolation, raw SQL, bulk update/delete, mass assignment, and sensitive model serialization
- Hardcoded and Git-tracked credentials, with secret values excluded from findings
- API authentication, throttling, and unsafe resource exposure

Run `php artisan guard:rules` for the installed rule catalog. Configure modules, paths, thresholds, suppressions, policy models, and adapters in `config/laravel-guard.php`.

## Tenant Protection

Bind a resolver and apply the trait to every tenant-owned model:

```php
use LaravelGuard\Tenant\Contracts\TenantResolver;
use LaravelGuard\Tenant\GuardsTenant;

$this->app->bind(TenantResolver::class, AppTenantResolver::class);

final class Patient extends Model
{
    use GuardsTenant;
}
```

Configure model classes and tables under `tenant.models` and `tenant.tables`. Runtime DB inspection is disabled by default and is best introduced in `local` and `testing` before production. Resolver adapters are included for `spatie/laravel-multitenancy` and `stancl/tenancy`.

## Runtime Upload Inspection

Add the `guard.uploads` middleware to endpoints that accept files. It compares the declared MIME type with the server-detected signature, rejects embedded PHP, Unix and Windows executable markers, and can enforce `uploads.allowed_detected_mimes`.

```php
Route::post('/documents', StoreDocumentController::class)
    ->middleware(['auth', 'guard.uploads']);
```

Static upload rules still verify validation and storage behavior, so teams can use either layer or both.

## Authorization And Policies

Route inspection recognizes Laravel authentication and authorization middleware, controller `authorize()` and Gate calls, controller-wide `authorizeResource()`, and Spatie Permission middleware. List security-critical model classes under `routes.policy_models` to report models without a registered policy.

## Suppressions

Prefer a narrow suppression with a documented reason:

```php
use LaravelGuard\Attributes\GuardIgnore;

#[GuardIgnore('LG-QUERY-002', reason: 'Static internal expression; no request input')]
final class MonthlyRollup {}
```

Configuration suppressions can target a route, file, symbol, or fingerprint. Scoped runtime exceptions use `LaravelGuard::allow($ruleId, $reason, $callback)` and never disable unrelated rules globally.

## Optional Integrations

Set `integrations.telescope` or `integrations.debugbar` to `true` to publish a metadata-only scan summary when the corresponding package is installed. Spatie Permission middleware is recognized without changing application authorization behavior.

The optional [PHPStan extension](docs/PHPSTAN.md) verifies that configured tenant model classes implement `TenantOwned` or use `GuardsTenant`.

## Testing And CI

Use `LaravelGuard\Testing\LaravelGuardAssertions` in a PHPUnit test case for `assertNoSecurityFindings()`, `assertRouteRequiresAuthentication()`, `assertRouteRequiresAuthorization()`, and `assertTenantSafe()`.

```yaml
- name: Laravel Guard
  run: php artisan guard:check --fail-on=high --format=github
```

The package includes focused regression, adversarial upload, and property-style test cases. See [performance guidance](docs/PERFORMANCE.md), [implementation status](docs/ROADMAP.md), [security policy](SECURITY.md), and [contributing guide](CONTRIBUTING.md).

## Extending Laravel Guard

Implement `LaravelGuard\Core\Contracts\GuardRule` and list the class under `custom_rules`. Custom reporters implement `LaravelGuard\Core\Contracts\SecurityReporter` and are mapped by format name under `reporters`.

## License

MIT
