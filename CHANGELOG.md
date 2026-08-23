# Changelog

All notable changes to this project are documented here. The project follows Semantic Versioning after 1.0.

## Unreleased

### Added

- Lowest- and highest-dependency compatibility jobs for Laravel 10-13.
- Windows CI with real Git baseline and changed-line integration coverage.
- Negative Composer checks for unsupported PHP and Laravel combinations.

## [0.1.1] - 2026-08-23

### Fixed

- Corrected `guard:rules` formatting for the newest Laravel Pint release.
- Allowed CI to resolve end-of-life Laravel 10 and 11 fixtures explicitly while retaining strict dependency audits for supported Laravel releases.
- Ignored Laravel 12's framework-owned signed local-storage route by default and added configurable route-name/URI exclusions.
- Updated GitHub release and provenance actions to their current supported major versions.

## [0.1.0] - 2026-08-23

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
- Operational Doctor checks for suppressions, policy models, Git context, and report destinations, plus working fingerprint and route suppression targets.

### Changed

- Invalid configured custom rules are reported by Doctor and prevent incomplete scans from running.
