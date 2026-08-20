# Laravel Guard implementation status

This document tracks the implementation blueprint. “Complete” means a tested first implementation exists; public APIs may still change before v1.

## Implemented

- Laravel 10-13 / PHP 8.2+ package discovery, configuration publishing, facade, rule registry, and custom rules
- AST source index with name/symbol annotation, per-content caching, `GuardIgnore`, config suppressions, stable normalized fingerprints, and scoped security exceptions
- Configuration, route, tenant, upload, query, model, secret, API, and optional runtime modules
- Tenant Eloquent scope and assignment, cross-tenant blocking, query event collection, re-entrancy-safe request context, Spatie Multitenancy resolver, and stancl/tenancy resolver
- Controller authorization call inspection plus recognized authentication, authorization, throttle, signed-link, and administrative route middleware
- Console, JSON, SARIF 2.1, GitHub annotation, JUnit, HTML, log, and custom reporter output
- Severity/confidence model, security score with limitations, baseline snapshots, changed-line Git diff, and CI thresholds
- `guard:scan`, `guard:check`, `guard:diff`, `guard:baseline`, `guard:rules`, and `guard:benchmark`
- PHPUnit route, finding, and runtime tenant assertions
- Responsible disclosure policy, contribution guide, compatibility workflow, and benchmark command
- A multi-organization hospital SaaS consumer exercising runtime tenant protection and all static modules

## Pending Before v1

- True file-content/MIME signature inspection requires a runtime upload hook; static rules currently verify validation declarations and storage behavior
- Security diff reports introduced findings on changed lines, but does not yet reconstruct and display resolved findings from a historical Git worktree
- Authorization analysis is method-local; interprocedural call graphs, policy auto-discovery, and data-flow taint analysis are not implemented
- First-party Telescope and Debugbar panels and a Spatie Permission presentation adapter remain; current middleware recognition and vendor-neutral extension points work without them
- PHPStan extension rules, mutation/fuzz testing, signed release automation, changelog policy, and a documented backwards-compatibility promise are still required for a stable v1 tag

## Post-v1

- Framework-wide taint analysis and IDE/LSP diagnostics
- Runtime request correlation, OpenTelemetry export, and centralized rule policy distribution
- Optional hosted findings dashboard, organization policy packs, and pull-request trend analytics
- Additional database and tenancy adapters driven by community demand

Laravel Guard remains one defense layer. It does not replace code review, dependency scanning, dynamic testing, infrastructure hardening, or penetration testing.
