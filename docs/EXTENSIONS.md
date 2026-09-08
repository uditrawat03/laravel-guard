# Extension API

Laravel Guard v1 exposes a versioned extension API for custom rules, reporters, finding integrations, scan events, findings, tenant resolvers, and configuration. The current API version is available as `LaravelGuard\Core\Extensions\ExtensionApi::VERSION`.

## Stable public contracts

The following types are covered by semantic-versioning compatibility from v1.0:

- `LaravelGuard\Core\Contracts\GuardRule`
- `LaravelGuard\Core\Contracts\SecurityContext`
- `LaravelGuard\Core\Contracts\SecurityReporter`
- `LaravelGuard\Core\Findings\SecurityFinding`
- `LaravelGuard\Core\Findings\FindingCollection`
- `LaravelGuard\Core\Findings\Severity`
- `LaravelGuard\Core\Findings\Confidence`
- `LaravelGuard\Integrations\Contracts\FindingIntegration`
- `LaravelGuard\Integrations\Events\SecurityScanCompleted`
- `LaravelGuard\Tenant\Contracts\TenantResolver`
- `LaravelGuard\Tenant\Contracts\TenantOwned`
- `LaravelGuard\Runtime\SecurityEvent`
- `LaravelGuard\Core\Extensions\ExtensionApi`
- `LaravelGuard\Core\Extensions\ExtensionConformance`

Commands, package configuration keys documented in `config/laravel-guard.php`, and machine-output schemas are also stable surfaces. Classes outside this list are package internals unless another public document explicitly says otherwise.

## Rule requirements

Rule IDs must be globally namespaced, uppercase, and end in three digits, for example `ACME-AUTH-001`. Names and descriptions must be non-empty. Categories use lowercase kebab-case. A rule must emit only `SecurityFinding` instances whose rule ID and category match the rule definition.

Duplicate IDs fail registration. Packages should use an organization or product prefix rather than the reserved `LG-` prefix.

```php
use LaravelGuard\Core\Extensions\ExtensionConformance;

ExtensionConformance::assertRule($rule, app(SecurityContext::class));
```

The same validator checks reporters and optional integrations:

```php
ExtensionConformance::assertReporter($reporter);
ExtensionConformance::assertIntegration($integration);
```

These checks throw an `InvalidArgumentException` for invalid definitions and an `UnexpectedValueException` for invalid runtime output. They do not depend on PHPUnit, so extension authors can call them from PHPUnit, Pest, a package command, or CI bootstrap code.

## Compatibility rules

- Minor releases may add classes, optional configuration keys with defaults, event types, enum cases only where consumers are documented to handle unknown values, and optional fields in machine output permitted by its schema.
- Minor releases do not add methods to stable interfaces, change constructor parameters on stable value objects, reuse a rule ID, or change an existing schema incompatibly.
- Breaking contract changes require a new major release. Independent machine schemas increment their own integer version.
- Deprecated APIs remain functional for at least two minor releases when possible, carry an `@deprecated` annotation, and include a replacement in the changelog and upgrading guide.
- Security fixes may tighten validation or fail closed without a deprecation window when preserving behavior would leave consumers exposed. The security impact and migration are documented in the release.

## Configuration compatibility

`ExtensionApi::CONFIGURATION_VERSION` identifies the documented configuration contract. Consumers should publish configuration once and merge new defaults during upgrades. New optional keys receive safe package defaults; removed or renamed keys require a major release or a documented compatibility bridge.

## Output compatibility

`ExtensionApi::outputSchemas()` reports current schema versions. Consumers must branch on both `schema` and `version`, ignore unknown optional properties, and reject unsupported major schema versions. See [Output schemas](OUTPUT_SCHEMAS.md) and [Upgrading](UPGRADING.md).