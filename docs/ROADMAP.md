# Laravel Guard implementation status

This document tracks the implementation blueprint. "Complete" means a tested first implementation exists; public APIs may still change before v1.

## Implemented

- Laravel 10-13 and PHP 8.2+ package discovery, configuration publishing, facade, rule registry, and custom rules
- AST source index with name and symbol annotation, per-content caching, `GuardIgnore`, config suppressions, stable normalized fingerprints, and scoped security exceptions
- Configuration, route, tenant, upload, query, model, secret, API, and optional runtime modules
- Tenant Eloquent scope and assignment, cross-tenant blocking, query event collection, re-entrancy-safe request context, Spatie Multitenancy resolver, and stancl/tenancy resolver
- Controller authorization inspection, `authorizeResource`, configured policy registration checks, and recognized Laravel and Spatie route middleware
- Runtime upload MIME/signature inspection, executable-content rejection, optional MIME allowlists, and metadata-only events
- Console, JSON, SARIF 2.1, GitHub annotation, JUnit, HTML, log, and custom reporter output
- Severity/confidence model, security score with limitations, baseline snapshots, and Git-aware introduced/resolved security diffs
- `guard:scan`, `guard:check`, `guard:diff`, `guard:baseline`, `guard:rules`, and `guard:benchmark`
- PHPUnit route, finding, runtime tenant, upload adversarial, and integration tests
- Optional Telescope and Debugbar summary publishing, Spatie Permission recognition, and PHPStan tenant-model rules
- Compatibility CI, provenance-attested release automation, changelog policy, security policy, and documented backwards compatibility
- A multi-organization hospital SaaS consumer exercising runtime tenant protection and all static modules

## Remaining Before v1

- Exercise the compatibility matrix and release workflow on the public repository, then resolve any environment-specific failures
- Run mutation testing across the full rule catalog and raise coverage where surviving mutations expose weak assertions
- Stabilize public extension APIs using feedback from real consumer applications

## Post-v1

- Interprocedural call graphs and framework-wide data-flow taint analysis
- Rich first-party Telescope and Debugbar panels beyond metadata-only scan summaries
- IDE/LSP diagnostics, runtime request correlation, OpenTelemetry export, and centralized rule policy distribution
- Optional hosted findings dashboard, organization policy packs, and pull-request trend analytics
- Additional database and tenancy adapters driven by community demand

Laravel Guard remains one defense layer. It does not replace code review, dependency scanning, dynamic testing, infrastructure hardening, or penetration testing.
