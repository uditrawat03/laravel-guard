# Laravel Guard

## Unified Security and Multi-Tenancy Protection Framework for Laravel

**Laravel Guard** is a modular Laravel security framework designed to detect application-level security risks, tenant isolation problems, insecure configuration, unsafe routes, risky database access, insecure uploads, and other Laravel-specific vulnerabilities during development, testing, CI, and optionally runtime.

The project should begin as a single umbrella package with internally separated security modules. Over time, individual modules can be extracted into standalone packages while preserving a shared core.

> **Core vision:** Make security mistakes in Laravel applications visible before they reach production.

---

# 1. Project Goals

Laravel Guard should provide a unified framework for:

- Tenant isolation protection
- Route security analysis
- Authorization checks
- Authentication security checks
- Configuration security analysis
- Database query security analysis
- Unsafe raw SQL detection
- Mass-assignment analysis
- File-upload security analysis
- API security checks
- Rate-limit detection
- CORS configuration analysis
- Session and cookie security checks
- Secret and credential detection
- Production configuration validation
- Security-focused test assertions
- CI security checks
- Security diff analysis for pull requests
- Structured security reports
- Extensible security-rule development

The package should focus on **Laravel-aware security analysis**, rather than attempting to replace general-purpose SAST, dependency scanners, or infrastructure security tools.

---

# 2. Non-Goals

Laravel Guard should not initially:

- Replace Laravel authentication
- Replace Laravel authorization policies
- Replace tenancy packages
- Replace dependency vulnerability scanners
- Replace OWASP ZAP or external penetration testing
- Automatically modify application code
- Automatically block every suspicious request in production
- Attempt full PHP static analysis from the first release
- Become an infrastructure or operating-system security scanner
- Manage firewalls or network security

Laravel Guard should detect, explain, and report risks.

---

# 3. Umbrella Architecture

The initial project should be distributed as:

```text
laravel-guard
```

Internally, the system should be modular.

```text
Laravel Guard
│
├── Core
│
├── Tenant Guard
├── Route Guard
├── Authorization Guard
├── Query Guard
├── Upload Guard
├── Configuration Guard
├── API Guard
├── Secret Guard
├── Runtime Guard
├── Testing Guard
└── CI / Security Diff
```

Later, modules may optionally become separate packages:

```text
laravel-guard/core

laravel-guard/tenant
laravel-guard/routes
laravel-guard/queries
laravel-guard/uploads
laravel-guard/config
laravel-guard/api
laravel-guard/secrets
```

All modules should continue to depend on the same core contracts.

---

# 4. Proposed Project Structure

```text
laravel-guard/
│
├── src/
│   │
│   ├── LaravelGuardServiceProvider.php
│   ├── LaravelGuard.php
│   │
│   ├── Core/
│   │   ├── Contracts/
│   │   │   ├── GuardRule.php
│   │   │   ├── SecurityScanner.php
│   │   │   ├── SecurityReporter.php
│   │   │   ├── SecurityContext.php
│   │   │   └── SuppressionResolver.php
│   │   │
│   │   ├── Rules/
│   │   │   ├── AbstractGuardRule.php
│   │   │   └── RuleRegistry.php
│   │   │
│   │   ├── Findings/
│   │   │   ├── SecurityFinding.php
│   │   │   ├── Severity.php
│   │   │   ├── Confidence.php
│   │   │   └── FindingCollection.php
│   │   │
│   │   ├── Reporting/
│   │   │   ├── ConsoleReporter.php
│   │   │   ├── JsonReporter.php
│   │   │   ├── LogReporter.php
│   │   │   └── GithubReporter.php
│   │   │
│   │   └── Support/
│   │       ├── FileLocator.php
│   │       ├── SourceLocation.php
│   │       └── StackTraceCleaner.php
│   │
│   ├── Tenant/
│   │   ├── Contracts/
│   │   │   ├── TenantResolver.php
│   │   │   └── TenantOwned.php
│   │   ├── TenantContext.php
│   │   ├── GuardsTenant.php
│   │   ├── TenantQueryInspector.php
│   │   └── Rules/
│   │       ├── MissingTenantConstraint.php
│   │       ├── CrossTenantAccess.php
│   │       ├── MissingTenantContext.php
│   │       ├── UnsafeTenantUpdate.php
│   │       └── UnsafeTenantDelete.php
│   │
│   ├── Routes/
│   │   ├── RouteScanner.php
│   │   └── Rules/
│   │       ├── MissingAuthentication.php
│   │       ├── MissingAuthorization.php
│   │       ├── MissingRateLimit.php
│   │       ├── SensitivePublicRoute.php
│   │       └── UnsafeHttpMethod.php
│   │
│   ├── Queries/
│   │   ├── QueryInspector.php
│   │   └── Rules/
│   │       ├── PotentialSqlInjection.php
│   │       ├── UnsafeRawQuery.php
│   │       ├── UnsafeBulkUpdate.php
│   │       └── UnsafeBulkDelete.php
│   │
│   ├── Uploads/
│   │   ├── UploadScanner.php
│   │   └── Rules/
│   │       ├── MissingUploadValidation.php
│   │       ├── UnsafeOriginalFilename.php
│   │       ├── DangerousExtension.php
│   │       └── PublicExecutableUpload.php
│   │
│   ├── Configuration/
│   │   ├── ConfigurationScanner.php
│   │   └── Rules/
│   │       ├── ProductionDebugEnabled.php
│   │       ├── WeakSessionConfiguration.php
│   │       ├── UnsafeCookieConfiguration.php
│   │       ├── UnsafeCorsConfiguration.php
│   │       └── InsecureFilesystemConfiguration.php
│   │
│   ├── Models/
│   │   └── Rules/
│   │       ├── UnsafeMassAssignment.php
│   │       └── SensitiveAttributeExposure.php
│   │
│   ├── Secrets/
│   │   ├── SecretScanner.php
│   │   └── Rules/
│   │       ├── HardcodedSecret.php
│   │       └── CommittedCredential.php
│   │
│   ├── Api/
│   │   └── Rules/
│   │       ├── MissingApiAuthentication.php
│   │       ├── MissingApiRateLimit.php
│   │       └── UnsafeApiResourceExposure.php
│   │
│   ├── Runtime/
│   │   ├── RuntimeMonitor.php
│   │   └── SecurityEventCollector.php
│   │
│   ├── Testing/
│   │   ├── LaravelGuardAssertions.php
│   │   └── SecurityTestCase.php
│   │
│   ├── Commands/
│   │   ├── ScanCommand.php
│   │   ├── CheckCommand.php
│   │   ├── DiffCommand.php
│   │   ├── TenantCheckCommand.php
│   │   └── ListRulesCommand.php
│   │
│   └── Integrations/
│       ├── Github/
│       ├── SpatieMultitenancy/
│       ├── StanclTenancy/
│       ├── Telescope/
│       └── Debugbar/
│
├── config/
│   └── laravel-guard.php
│
├── tests/
│   ├── Unit/
│   ├── Feature/
│   ├── Fixtures/
│   └── Integration/
│
├── docs/
│
├── composer.json
├── phpunit.xml
├── LICENSE
└── README.md
```

---

# 5. Core Rule System

Every security check should implement a shared contract.

```php
interface GuardRule
{
    public function id(): string;

    public function name(): string;

    public function description(): string;

    public function severity(): Severity;

    public function scan(SecurityContext $context): iterable;
}
```

Rules return zero or more findings.

Example:

```php
final class ProductionDebugEnabled implements GuardRule
{
    public function id(): string
    {
        return 'LG-CONFIG-001';
    }

    public function severity(): Severity
    {
        return Severity::Critical;
    }

    public function scan(SecurityContext $context): iterable
    {
        if (
            app()->environment('production') &&
            config('app.debug') === true
        ) {
            yield SecurityFinding::fromRule($this)
                ->message('APP_DEBUG is enabled in production.');
        }
    }
}
```

This rule architecture should be the foundation of every module.

---

# 6. Security Finding Model

All modules should produce the same structured finding object.

```php
SecurityFinding {
    ruleId
    category
    severity
    confidence
    title
    description
    risk
    recommendation
    file
    line
    metadata
}
```

Example:

```text
Rule:
LG-ROUTE-002

Severity:
HIGH

Confidence:
HIGH

Title:
Sensitive route may be missing authorization

Route:
DELETE /admin/users/{user}

Location:
routes/web.php:84

Risk:
An authenticated user may be able to perform an administrative action without permission validation.

Recommendation:
Attach authorization middleware or verify authorization through a policy, gate, or controller authorization call.
```

---

# 7. Rule Naming Convention

Use consistent rule identifiers.

```text
LG-TENANT-001
LG-ROUTE-001
LG-AUTH-001
LG-QUERY-001
LG-UPLOAD-001
LG-CONFIG-001
LG-API-001
LG-SECRET-001
LG-MODEL-001
LG-RUNTIME-001
```

Example mappings:

```text
LG-TENANT-001 Missing Tenant Constraint
LG-TENANT-002 Cross-Tenant Access
LG-ROUTE-001 Missing Authentication
LG-ROUTE-002 Missing Authorization
LG-ROUTE-003 Missing Rate Limiting
LG-QUERY-001 Potential SQL Injection
LG-UPLOAD-001 Missing Upload Validation
LG-CONFIG-001 Production Debug Enabled
LG-SECRET-001 Potential Hardcoded Secret
```

---

# 8. Severity Levels

Use four initial levels:

```text
LOW
MEDIUM
HIGH
CRITICAL
```

Suggested meaning:

### LOW

Security hardening recommendation with limited immediate risk.

### MEDIUM

Potential security weakness requiring developer review.

### HIGH

Likely security vulnerability or dangerous application behavior.

### CRITICAL

High-impact vulnerability that could expose sensitive data, authentication, tenant isolation, or destructive operations.

---

# 9. Confidence Levels

Security scanners often create false positives.

Every finding should therefore include confidence:

```text
LOW
MEDIUM
HIGH
```

Example:

```text
Severity: HIGH
Confidence: MEDIUM
```

This distinguishes vulnerability impact from detection certainty.

---

# 10. Tenant Guard Module

Tenant Guard detects unsafe access to tenant-owned resources.

Tenant-owned models can use:

```php
class Project extends Model
{
    use GuardsTenant;
}
```

Or implement:

```php
interface TenantOwned
{
    public function tenantColumn(): string;
}
```

Tenant resolution should use:

```php
interface TenantResolver
{
    public function currentTenantId(): string|int|null;
}
```

## Initial Rules

```text
LG-TENANT-001 Missing Tenant Constraint
LG-TENANT-002 Cross-Tenant Access
LG-TENANT-003 Missing Tenant Context
LG-TENANT-004 Unsafe Tenant Bulk Update
LG-TENANT-005 Unsafe Tenant Bulk Delete
LG-TENANT-006 Unsafe Raw Tenant Query
```

Example violation:

```text
CRITICAL

LG-TENANT-002 Cross-Tenant Access

Current Tenant:
42

Requested Tenant:
57

Model:
App\Models\Project

Source:
app/Services/ProjectService.php:92
```

---

# 11. Route Guard Module

Route Guard analyzes Laravel's registered route collection.

Inspect:

```php
app('router')->getRoutes();
```

Each route should be analyzed for:

- HTTP method
- URI
- Controller
- Middleware
- Authentication
- Authorization
- Throttling
- Signed middleware
- Domain
- Route name

## Initial Rules

```text
LG-ROUTE-001 Sensitive Route Missing Authentication
LG-ROUTE-002 Sensitive Route Missing Authorization
LG-ROUTE-003 Missing Rate Limiting
LG-ROUTE-004 Public Administrative Route
LG-ROUTE-005 Sensitive GET Action
LG-ROUTE-006 Unsigned Sensitive Action
```

Example:

```text
HIGH

DELETE /admin/users/{user}

Authentication:
auth

Authorization:
Not detected

Controller:
UserController@destroy

Recommendation:
Add policy, gate, can middleware, or controller authorization.
```

---

# 12. Authorization Guard

Authorization detection should understand common Laravel patterns.

Recognize:

```php
$this->authorize(...)
```

```php
Gate::authorize(...)
```

```php
Gate::allows(...)
```

```php
->middleware('can:update,project')
```

```php
Route::can(...)
```

Laravel policies should also be inspected when possible.

Avoid assuming that middleware absence automatically means authorization is absent.

Use confidence levels.

---

# 13. Query Guard

Query Guard should inspect potentially dangerous database operations.

Examples:

```php
DB::raw(...)
```

```php
DB::statement(...)
```

```php
DB::select(...)
```

```php
whereRaw(...)
```

```php
orderByRaw(...)
```

```php
selectRaw(...)
```

The package should distinguish parameterized raw SQL from direct interpolation.

Unsafe example:

```php
DB::select(
    "SELECT * FROM users WHERE email = '$email'"
);
```

Safer example:

```php
DB::select(
    'SELECT * FROM users WHERE email = ?',
    [$email]
);
```

## Initial Rules

```text
LG-QUERY-001 Potential SQL Injection
LG-QUERY-002 Unsafe Raw SQL
LG-QUERY-003 Unscoped Bulk Update
LG-QUERY-004 Unscoped Bulk Delete
```

---

# 14. Upload Guard

Laravel Guard should analyze file upload handling.

Common risks:

- Missing file validation
- Original user filename reused
- Executable file uploads
- Public storage of dangerous content
- SVG uploads without sanitization
- Missing size limits
- MIME and extension mismatch
- Path traversal possibilities

Unsafe:

```php
$file->move(
    public_path('uploads'),
    $file->getClientOriginalName()
);
```

Prefer:

```php
$request->validate([
    'document' => [
        'required',
        'file',
        'mimes:pdf',
        'max:10240',
    ],
]);

$path = $request
    ->file('document')
    ->store('documents');
```

## Initial Rules

```text
LG-UPLOAD-001 Missing Upload Validation
LG-UPLOAD-002 User-Controlled Filename
LG-UPLOAD-003 Dangerous Upload Extension
LG-UPLOAD-004 Public Executable Upload
LG-UPLOAD-005 Missing Upload Size Restriction
```

---

# 15. Configuration Guard

Configuration Guard analyzes Laravel environment and configuration security.

Initial checks:

```text
APP_DEBUG
APP_ENV
APP_KEY
session secure cookie
session http_only
session same_site
CORS configuration
trusted proxies
filesystem visibility
logging configuration
database SSL settings
mail configuration
```

## Initial Rules

```text
LG-CONFIG-001 Production Debug Enabled
LG-CONFIG-002 Missing Application Key
LG-CONFIG-003 Insecure Session Cookie
LG-CONFIG-004 Missing HttpOnly Session Cookie
LG-CONFIG-005 Unsafe SameSite Configuration
LG-CONFIG-006 Overly Broad CORS
LG-CONFIG-007 Public Sensitive Filesystem
```

---

# 16. Model Guard

Inspect Eloquent model security.

Potential checks:

- `$guarded = []`
- Sensitive attributes included in `$fillable`
- Sensitive data missing from `$hidden`
- Unsafe casts
- Security-sensitive attributes exposed through serialization

Example:

```php
protected $guarded = [];
```

could produce:

```text
LG-MODEL-001

Potential unrestricted mass assignment.

Model:
App\Models\User

Severity:
MEDIUM
```

Do not automatically classify every `$guarded = []` as a vulnerability.

Laravel applications may intentionally use validated DTOs or trusted assignment.

Confidence should reflect context.

---

# 17. Secret Guard

Secret Guard should scan application source and configuration for accidentally committed credentials.

Potential secret categories:

- API keys
- Private keys
- Access tokens
- Passwords
- Database URLs
- Cloud credentials

Do not print the complete secret in reports.

Example:

```text
HIGH

LG-SECRET-001

Potential API credential detected.

File:
config/services.php:18

Value:
sk_live_************

Recommendation:
Move the credential to an environment variable and rotate it if it has already been committed.
```

---

# 18. API Guard

API Guard should focus on Laravel API security patterns.

Checks:

- Authentication middleware
- Authorization
- Rate limits
- Sensitive resource exposure
- Unsafe API debug output
- Public destructive routes
- Missing signed requests when configured
- Sanctum or Passport configuration where applicable

Example:

```text
POST /api/admin/export-users

Authentication:
Not detected

Rate limit:
Not detected

Severity:
CRITICAL
```

---

# 19. Runtime Guard

Runtime protection should be optional.

Initial runtime capabilities may include:

- Query inspection
- Tenant isolation monitoring
- Dangerous cross-tenant query detection
- Security event collection
- Unsafe SQL detection
- Unexpected privileged actions

Runtime Guard should default to:

```text
local
testing
```

Production runtime inspection should be opt-in.

Performance impact must be benchmarked before recommending production use.

---

# 20. Suppressions

False positives must be manageable.

Support configuration suppression:

```php
'ignore' => [
    'LG-MODEL-001' => [
        App\Models\ImportBuffer::class,
    ],
],
```

Also consider code-level suppressions:

```php
#[GuardIgnore(
    rule: 'LG-MODEL-001',
    reason: 'Validated DTO controls all input'
)]
class ImportBuffer extends Model
{
}
```

Every suppression should require or strongly encourage a reason.

Avoid silent blanket suppression.

---

# 21. Explicit Security Exceptions

For intentional operations such as cross-tenant administration:

```php
LaravelGuard::allow(
    rule: 'LG-TENANT-002',
    reason: 'Super admin reporting',
    callback: fn () => Project::all(),
);
```

This is preferable to globally disabling the rule.

---

# 22. Primary CLI

Main scanner:

```bash
php artisan guard:scan
```

Example output:

```text
Laravel Guard

Security Scan

CRITICAL    2
HIGH        4
MEDIUM      7
LOW         3

16 findings detected.

Security score:
68 / 100
```

Detailed output:

```text
CRITICAL
LG-CONFIG-001
Production debug mode enabled

HIGH
LG-ROUTE-002
Sensitive route may be missing authorization

HIGH
LG-UPLOAD-002
User-controlled upload filename detected
```

---

# 23. CI Command

Provide:

```bash
php artisan guard:check
```

Unlike `guard:scan`, this command should support failing the process.

Example:

```bash
php artisan guard:check --fail-on=high
```

Return code:

```text
0 = pass
1 = security threshold exceeded
```

GitHub Actions:

```yaml
- name: Laravel Guard
  run: php artisan guard:check --fail-on=high
```

---

# 24. Security Diff

One of the strongest differentiators should be:

```bash
php artisan guard:diff main
```

Instead of flooding developers with all historical security issues, analyze security changes introduced by the current branch.

Example:

```text
Laravel Guard Security Diff

Base:
main

Current:
feature/file-import

New Findings

+ HIGH
  LG-UPLOAD-002
  Unsafe original filename

+ MEDIUM
  LG-ROUTE-003
  Missing rate limiter

Resolved Findings

- HIGH
  LG-QUERY-001
  Potential SQL injection

Security Risk Change

Before: B
After:  C
```

This feature is especially useful for pull requests.

---

# 25. Baseline Support

Legacy applications may already contain findings.

Support:

```bash
php artisan guard:baseline
```

This stores existing findings.

CI then focuses on newly introduced issues.

Example:

```text
Existing findings:
47

New findings:
2

CI status:
FAILED
```

Baseline files should identify findings through stable fingerprints.

Example fingerprint:

```text
rule
file
line or AST location
relevant symbol
normalized code
```

---

# 26. Security Score

Optional scoring can improve developer visibility.

Example:

```text
Laravel Guard Security Score

92 / 100

Configuration       A
Routes              B
Authorization       A
Queries             A
Uploads             C
Tenant Isolation    A
Secrets             A
```

Avoid presenting the score as proof that an application is secure.

Documentation must clearly state:

> A high Laravel Guard score does not replace professional security review or penetration testing.

---

# 27. Testing Helpers

Provide Laravel-specific security assertions.

Examples:

```php
$this->assertRouteRequiresAuthentication(
    'admin.users.index'
);
```

```php
$this->assertRouteUsesMiddleware(
    'admin.users.destroy',
    'can:delete,user'
);
```

```php
$this->assertTenantSafe(function () {
    ProjectService::find($projectId);
});
```

```php
$this->assertNoSecurityFindings(
    severity: Severity::High
);
```

Potential base trait:

```php
use LaravelGuardAssertions;
```

---

# 28. Configuration

Proposed:

```php
return [

    'enabled' => env(
        'LARAVEL_GUARD_ENABLED',
        true
    ),

    'modules' => [

        'tenant' => true,
        'routes' => true,
        'authorization' => true,
        'queries' => true,
        'uploads' => true,
        'configuration' => true,
        'models' => true,
        'secrets' => true,
        'api' => true,
        'runtime' => false,

    ],

    'minimum_severity' => 'low',

    'ci' => [

        'fail_on' => 'high',

    ],

    'runtime' => [

        'enabled' => env(
            'LARAVEL_GUARD_RUNTIME',
            false
        ),

    ],

    'tenant' => [

        'column' => 'tenant_id',

    ],

    'ignore' => [],

];
```

---

# 29. Extensibility

Applications should be able to create custom rules.

Example:

```php
final class AdminRouteMustUseMfa implements GuardRule
{
    public function id(): string
    {
        return 'APP-SEC-001';
    }

    public function scan(SecurityContext $context): iterable
    {
        // Application-specific security check
    }
}
```

Register:

```php
LaravelGuard::registerRule(
    AdminRouteMustUseMfa::class
);
```

This allows organizations to encode internal security requirements.

---

# 30. Framework Integrations

Future adapters may support:

## Laravel Sanctum

Check API authentication patterns.

## Laravel Passport

Inspect OAuth configuration.

## Spatie Permission

Understand role and permission middleware.

## Spatie Multitenancy

Automatically resolve tenant context.

## stancl/tenancy

Automatically resolve tenant context.

## Laravel Telescope

Display Laravel Guard security events.

## Laravel Debugbar

Show findings during local development.

## GitHub

Add pull-request security annotations.

---

# 31. Security Report Formats

Support multiple reporters.

Console:

```bash
php artisan guard:scan
```

JSON:

```bash
php artisan guard:scan --format=json
```

Potential future formats:

```text
JSON
SARIF
HTML
JUnit
GitHub annotations
```

SARIF support would allow integration with code-scanning platforms.

---

# 32. Performance Requirements

Laravel Guard should avoid making normal development significantly slower.

Guidelines:

- Cache route analysis where possible
- Avoid scanning vendor directories
- Avoid repeated source parsing
- Disable expensive scanners by configuration
- Keep runtime rules lightweight
- Perform static project scanning primarily through CLI
- Benchmark every runtime hook

Target:

```text
Runtime monitoring overhead should remain minimal and measurable.
```

---

# 33. Security Principles

## Fail Safe

When Laravel Guard is uncertain, report carefully rather than modifying application behavior.

## Explain Every Finding

Every result should contain:

```text
What happened
Why it matters
Where it occurred
How confident the scanner is
How to investigate or remediate it
```

## Minimize False Positives

Security tools that produce excessive noise eventually get ignored.

## Never Leak Secrets

Detected credentials must always be masked.

## Do Not Execute Untrusted Code

Static scanners should parse source rather than evaluating arbitrary application strings.

## Secure the Security Tool

Laravel Guard itself must have strong tests, minimal dependencies, and careful handling of application data.

---

# 34. Initial MVP

Do not build every module immediately.

The MVP should focus on four high-value areas:

```text
Tenant Guard
Route Guard
Configuration Guard
Upload Guard
```

Initial rules:

```text
LG-TENANT-001 Missing Tenant Constraint
LG-TENANT-002 Cross-Tenant Access

LG-ROUTE-001 Sensitive Route Missing Authentication
LG-ROUTE-002 Sensitive Route Missing Authorization
LG-ROUTE-003 Missing Rate Limiting

LG-CONFIG-001 Production Debug Enabled
LG-CONFIG-002 Weak Session Configuration
LG-CONFIG-003 Overly Broad CORS

LG-UPLOAD-001 Missing Upload Validation
LG-UPLOAD-002 User-Controlled Filename
```

Primary command:

```bash
php artisan guard:scan
```

---

# 35. Development Roadmap

## Phase 1 — Core

Build:

- Composer package
- Service provider
- Configuration
- Rule registry
- SecurityFinding
- Severity
- Confidence
- Console reporter

Release target:

```text
v0.1
```

---

## Phase 2 — Configuration Guard

Implement simple high-confidence checks.

Release target:

```text
v0.2
```

---

## Phase 3 — Route Guard

Analyze:

- Authentication middleware
- Authorization middleware
- Throttling
- Sensitive routes

Release target:

```text
v0.3
```

---

## Phase 4 — Tenant Guard

Implement:

- Tenant resolver
- Tenant-owned model detection
- Query monitoring
- Missing tenant constraint
- Cross-tenant detection

Release target:

```text
v0.4
```

---

## Phase 5 — Upload Guard

Detect unsafe upload patterns.

Release target:

```text
v0.5
```

---

## Phase 6 — Testing Support

Add:

```php
$this->assertTenantSafe(...);
```

and route-security assertions.

Release target:

```text
v0.6
```

---

## Phase 7 — CI

Implement:

```bash
php artisan guard:check
```

Add severity thresholds and exit codes.

Release target:

```text
v0.7
```

---

## Phase 8 — Baseline

Implement:

```bash
php artisan guard:baseline
```

Release target:

```text
v0.8
```

---

## Phase 9 — Security Diff

Implement:

```bash
php artisan guard:diff main
```

Release target:

```text
v0.9
```

---

## Phase 10 — Stable Release

Before v1:

- Benchmark runtime
- Reduce false positives
- Test supported Laravel versions
- Test PHP versions
- Complete documentation
- Add extension API
- Stabilize rule identifiers
- Publish security policy
- Establish responsible disclosure process

Release:

```text
v1.0
```

---

# 36. Post-v1 Roadmap

```text
v1.1
Query Guard

v1.2
Model Guard

v1.3
Secret Guard

v1.4
API Guard

v1.5
Spatie Permission integration

v1.6
Spatie Multitenancy integration

v1.7
stancl/tenancy integration

v1.8
SARIF and GitHub Code Scanning

v2.0
Static analysis engine

v2.1
PHPStan integration

v2.2
Security dashboard

v3.0
Optional hosted security reporting platform
```

---

# 37. Recommended CLI Experience

```bash
php artisan guard:scan
```

Scan everything.

```bash
php artisan guard:scan --module=routes
```

Scan one module.

```bash
php artisan guard:scan --severity=high
```

Show high and critical findings.

```bash
php artisan guard:check --fail-on=high
```

CI validation.

```bash
php artisan guard:diff main
```

Analyze security changes.

```bash
php artisan guard:baseline
```

Create baseline.

```bash
php artisan guard:rules
```

List available security rules.

---

# 38. Example Final Developer Experience

Developer installs:

```bash
composer require --dev laravel-guard/laravel-guard
```

Publishes configuration:

```bash
php artisan vendor:publish \
    --tag=laravel-guard-config
```

Runs:

```bash
php artisan guard:scan
```

Receives:

```text
Laravel Guard

Security Scan Complete

CRITICAL  1
HIGH      3
MEDIUM    4
LOW       2

────────────────────────────────────────

CRITICAL

LG-TENANT-002
Possible cross-tenant access

Model:
App\Models\Invoice

Current tenant:
21

Requested tenant:
38

Source:
app/Services/InvoiceService.php:42

────────────────────────────────────────

HIGH

LG-ROUTE-002
Sensitive route may be missing authorization

DELETE /admin/users/{user}

Source:
routes/web.php:84

────────────────────────────────────────

HIGH

LG-UPLOAD-002
User-controlled upload filename

Source:
app/Http/Controllers/UploadController.php:51

────────────────────────────────────────

Security Score

74 / 100

Result:
Action Recommended
```

---

# 39. GitHub Actions Example

```yaml
name: Security

on:
  pull_request:
  push:
    branches:
      - main

jobs:

  laravel-guard:

    runs-on: ubuntu-latest

    steps:

      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - name: Install dependencies
        run: composer install --no-interaction

      - name: Laravel Guard
        run: php artisan guard:check --fail-on=high
```

Later:

```bash
php artisan guard:diff origin/main
```

can be used for pull-request security validation.

---

# 40. Package Positioning

Laravel Guard should be positioned as:

> A Laravel-native security engineering toolkit that detects risky application patterns, tenant isolation failures, insecure routes, unsafe uploads, dangerous configuration, and security regressions before deployment.

It should not market itself as:

> A complete replacement for application security testing.

Instead:

```text
Laravel Guard
    +
Dependency scanning
    +
Static analysis
    +
External security testing
    +
Penetration testing
```

together provide stronger application security.

---

# 41. Long-Term Product Vision

The project can eventually evolve into three layers.

## Layer 1 — Open Source Package

```text
Laravel Guard
```

Local development and CI.

## Layer 2 — GitHub Security Integration

Security analysis directly inside pull requests.

```text
PR opened
   ↓
Laravel Guard runs
   ↓
New security findings identified
   ↓
Inline annotations
   ↓
Security check passes or fails
```

## Layer 3 — Optional SaaS Platform

Teams could optionally upload sanitized Laravel Guard reports.

Possible capabilities:

- Security history
- Team dashboards
- Project security posture
- Security regression tracking
- Rule management
- Suppression reviews
- Compliance evidence
- Pull-request trends
- Multi-project reporting

The open-source package should remain fully useful without the SaaS layer.

---

# 42. Most Important Design Decision

Do not begin by trying to detect every possible security vulnerability.

Start with a small number of **high-confidence Laravel-specific rules**.

The package will gain trust when developers see:

```text
10 findings
9 genuinely useful
```

rather than:

```text
500 findings
450 irrelevant
```

Detection quality should always take priority over the number of rules.

---

# 43. First Release Definition

The first public release is successful when a developer can:

```bash
composer require --dev laravel-guard/laravel-guard
```

then:

```bash
php artisan guard:scan
```

and receive useful findings about:

- Production configuration
- Sensitive routes
- Authorization
- Rate limiting
- Tenant isolation
- File uploads

with:

- Severity
- Confidence
- Source location
- Explanation
- Recommended remediation

That is enough to establish Laravel Guard as a real security package before expanding into more advanced analysis.
