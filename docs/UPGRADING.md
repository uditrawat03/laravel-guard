# Upgrading Laravel Guard

## Unreleased to the schema-versioned release

JSON reports and JSON diffs now include additive `schema` and `schema_version` fields. Consumers that reject unknown top-level keys must allow these fields. Consumers should then use them to reject unsupported future versions with a clear error.

Newly written baselines include `schema: laravel-guard/baseline` and continue using `schema_version: 3`. Schema-1 and schema-2 baselines remain readable. Regenerate a legacy baseline to add governance metadata.

SARIF remains version 2.1.0 and now includes Laravel Guard contract metadata under `runs[].properties`. JUnit adds two suite properties. Parsers that follow the respective formats should accept both additions.

No finding fields were removed or renamed in this release. Existing report, diff, baseline, SARIF, and JUnit consumers can migrate without transforming stored data.
## Runtime performance schema 1 to 2

`guard:benchmark-runtime --format=json` now writes schema version 2. It retains every v1 field and adds required `memory_growth_mb` and `state_leaks` fields; `scenario` also accepts `worker`. Consumers that validate the strict v1 schema should select `resources/schemas/runtime-performance-v2.json` before consuming new reports. The v1 schema remains packaged for stored reports.