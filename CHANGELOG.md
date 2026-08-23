# Changelog

All notable changes to this project are documented here. The project follows Semantic Versioning after 1.0.

## Unreleased

### Added

- Laravel-native configuration, route, query, model, secret, API, upload, and tenant analysis.
- Runtime tenant enforcement and upload signature inspection.
- Console, JSON, SARIF, GitHub, JUnit, HTML, and log reporting.
- Baselines and Git-aware introduced/resolved security diffs.
- Optional Telescope, Debugbar, Spatie Permission, tenancy, and PHPStan integrations.
- CI compatibility and provenance-attested release workflows.
- `guard:doctor` configuration and optional-integration diagnostics with strict CI and JSON modes.
- `guard:explain` rule guidance and a stable built-in rule reference.
- Governed schema-3 baselines with ownership, acceptance reasons, expiration, listing, explanation, pruning, legacy compatibility, and expired-finding resurfacing.
- Independently versioned JSON report, security diff, governed baseline, SARIF convention, and JUnit convention contracts with packaged JSON Schema definitions and upgrade guidance.

### Changed

- Invalid configured custom rules are reported by Doctor and prevent incomplete scans from running.
