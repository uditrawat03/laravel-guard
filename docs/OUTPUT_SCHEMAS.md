# Machine-readable output schemas

Laravel Guard versions each machine-readable contract independently. Consumers should check both `schema` and `schema_version` before parsing JSON produced by the package.

## Contracts

| Output | Identity | Version | Definition |
|---|---|---:|---|
| `guard:scan --format=json` and `guard:check --format=json` | `laravel-guard/report` | 1 | `resources/schemas/report-v1.json` |
| `guard:diff --format=json` | `laravel-guard/diff` | 1 | `resources/schemas/diff-v1.json` |
| `.laravel-guard-baseline.json` | `laravel-guard/baseline` | 3 | `resources/schemas/baseline-v3.json` |
| `guard:benchmark --format=json` | `laravel-guard/performance` | 1 | `resources/schemas/performance-v1.json` |
| `guard:benchmark-runtime --format=json` | `laravel-guard/runtime-performance` | 1 | `resources/schemas/runtime-performance-v1.json` |
| SARIF | SARIF 2.1.0 | Laravel Guard report contract 1 | Official SARIF schema plus run properties |
| JUnit | `laravel-guard/junit` | 1 | Suite properties documented below |

SARIF keeps its standard top-level `version` and `$schema`. Laravel Guard adds `runs[].properties.laravelGuardSchema` and `laravelGuardSchemaVersion`.

JUnit includes `laravel-guard.schema` and `laravel-guard.schema-version` properties in the generated test suite. Existing testcase and failure elements are unchanged.

## Compatibility policy

- New optional fields may be added within the same schema version.
- Required fields are not removed or renamed within a schema version.
- Meaning or type changes require a new schema version.
- New enum values may be added when Laravel Guard adds a severity, confidence, or status.
- Readers should ignore unknown fields and reject unsupported major schema versions clearly.
- Baseline schemas 1 and 2 remain readable. New baseline writes use schema 3.
- SARIF remains valid SARIF 2.1.0; the Laravel Guard property version covers package-specific conventions.

The PHP constants live in `LaravelGuard\Core\Support\OutputSchema`. Packaged JSON Schema documents use JSON Schema Draft 2020-12 and stable URN identifiers.

## Consumer example

```php
$report = json_decode(file_get_contents('guard.json'), true, flags: JSON_THROW_ON_ERROR);

if (($report['schema'] ?? null) !== 'laravel-guard/report' || ($report['schema_version'] ?? null) !== 1) {
    throw new RuntimeException('Unsupported Laravel Guard report schema.');
}

foreach ($report['findings'] as $finding) {
    // Ignore unknown fields so additive changes remain compatible.
}
```
