# Changelog

All notable changes to this project are documented here. The project follows Semantic Versioning after 1.0.

## Unreleased

### Added

- Canonical, safely configurable rule-documentation URLs in serialized findings, SARIF `helpUri` output, persisted dashboard evidence, and GUI finding actions.
- An opt-in, package-owned security dashboard with findings, scan history, baselines, rule catalog, runtime status, and configuration diagnostics.
- Fail-closed Gate authorization, a rate-limited browser scan action, redacted reports, private file-backed history, and retention controls.

### Changed

- Local and testing environments receive a secure authenticated-user fallback for the default dashboard Gate, while production remains fail closed until the consuming application defines the ability.
- Refined the dashboard layout, navigation, tables, filters, responsive behavior, and information hierarchy.

### Fixed

- Dashboard routes are now registered automatically during package discovery regardless of configuration-cache state; disabled dashboards still fail closed at middleware level.
- Separated dashboard browsing and scan submission rate-limit keys so normal navigation cannot cause a rescan to return HTTP 429.
- Replaced framework pagination markup with compact package-owned controls and fingerprinted dashboard assets to prevent stale styles after upgrades.
- The dashboard footer now reports the installed package version instead of historical scan metadata.

## [0.1.2] - 2026-08-24

### Added

- Lowest- and highest-dependency compatibility jobs for Laravel 10-13.
- Windows CI with real Git baseline and changed-line integration coverage.
- Negative Composer checks for unsupported PHP and Laravel combinations.
- Scheduled and manually dispatchable Packagist installation, rollback, and roll-forward verification.
- A release runbook covering distribution checks, immutable rollback response, and security hotfix handling.
- Dedicated upstream integration jobs for Spatie Permission 6-8, Spatie Multitenancy 3-4, stancl/tenancy 3, Telescope 5, Debugbar 3-4, and PHPStan 1-2.
- Real tenant transition, cleared-context, event dispatch, Debugbar collector, middleware alias, and passing/failing PHPStan fixtures.
- Enforceable `guard:benchmark` warm-P95 and peak-memory ceilings with cold/warm metrics, failing CI exit codes, and JSON output.
- A packaged performance-report v1 JSON Schema and public Laravel 13 performance workflow with downloadable results.
- A PCOV/Clover coverage workflow with a standalone verifier, downloadable report, GitHub summary, and enforced 75% statement threshold.
- A scheduled and manually dispatchable full-catalog Infection workflow with an enforced 43% MSI/covered-MSI floor and downloadable survivor reports.
- Repeatable `guard:benchmark --path` overrides for isolated representative source trees.
- `guard:benchmark-runtime` query/upload overhead budgets with a packaged runtime-performance v1 JSON Schema.
- Public small/medium/large scan and query/upload runtime matrices with downloadable reports.

### Changed

- Source files are enumerated and read once per scan context, avoiding repeated filesystem work across source-based rules.

### Fixed

- Added Debugbar 4's `Fruitcake` namespace while retaining Debugbar 3 compatibility.
- Registered the PHPStan extension parameter schema and moved tenant-model analysis to PHPStan's public class node so configured unsafe models are reported.


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
