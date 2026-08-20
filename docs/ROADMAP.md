# Laravel Guard implementation status

This file tracks the blueprint against the current package. Completed means a usable first implementation exists; it does not imply the API is stable for v1.

## Completed MVP

- Composer package discovery for Laravel 10 through 13 and PHP 8.2+
- Shared `GuardRule` contract, registry, scanner context, severity, confidence, findings, collections, and stable fingerprints
- Console and JSON reporters
- Configuration rules for production debug, session cookies, and broad credentialed CORS
- Route rules for authentication, authorization, and rate limiting
- Upload source rules for missing validation and client-controlled filenames
- Vendor-neutral tenant resolver, tenant-owned model contract, Eloquent global scope, automatic tenant assignment, and cross-tenant retrieval blocking
- Configuration and symbol/file suppressions
- Custom rule registration
- `guard:scan`, `guard:check`, `guard:baseline`, and `guard:rules`
- CI severity thresholds and baseline filtering
- Route and finding PHPUnit assertions
- Laravel 13 hospital consumer application

## Pending before v1

- PHP AST parsing to replace regular-expression upload analysis and improve controller-level authorization detection
- `GuardIgnore` attribute resolution; the attribute exists but the scanner does not yet read it
- Audited exception context for `LaravelGuard::allow`; it validates a reason but does not yet scope and record a specific rule exception
- Tenant query event findings for cross-tenant access, unsafe bulk update/delete, and raw tenant queries
- Upload rules for dangerous executable extensions, public executable storage, MIME mismatch, SVG sanitization, size limits, and path traversal
- Configuration checks for APP_KEY, trusted proxies, filesystem visibility, logging exposure, database TLS, and mail security
- Route rules for administrative public routes, unsafe GET actions, and missing signed middleware
- Policy/controller source inspection for `$this->authorize`, gates, and policy registration
- Security score with clearly documented limitations
- SARIF, GitHub annotation, log, HTML, and JUnit reporters
- `guard:diff` and Git-aware new/resolved finding comparison
- Baseline fingerprint resilience based on normalized code and AST symbols rather than line numbers
- Dedicated security policy and responsible-disclosure process
- Compatibility CI matrix across PHP 8.2–8.5 and Laravel 10–13
- Performance benchmarks and scanner caching

## Post-v1 modules

- Query Guard: SQL interpolation, unsafe raw SQL, unscoped bulk mutations
- Model Guard: mass assignment and sensitive serialization
- Secret Guard with mandatory value masking
- API Guard for Sanctum, Passport, resource exposure, and API debug output
- Optional Runtime Guard and security event collection
- Spatie Permission, Spatie Multitenancy, stancl/tenancy, Telescope, and Debugbar integrations
- PHPStan integration and deeper static-analysis engine
- Optional reporting dashboard and hosted reporting layer

The package remains an application-aware security aid. A clean scan does not replace code review, dependency scanning, dynamic testing, or penetration testing.
