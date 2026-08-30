# Rule reference

This reference lists every built-in static rule. Run `php artisan guard:explain LG-...` for module-specific impact, response guidance, analysis limitations, and structured vulnerable/safer code examples. The package-owned dashboard exposes the same guidance from each expandable rule row.

Severity is the default rating. A finding must still be reviewed in the context of the application. Suppress only a demonstrated false positive with a narrow scope and documented reason.

## Configuration

| Rule | Severity | Detects |
| --- | --- | --- |
| <a id="lg-config-001"></a>`LG-CONFIG-001` | Critical | Production debug mode enabled |
| <a id="lg-config-002"></a>`LG-CONFIG-002` | High | Weak session configuration |
| <a id="lg-config-003"></a>`LG-CONFIG-003` | High | Overly broad CORS policy |
| <a id="lg-config-004"></a>`LG-CONFIG-004` | Critical | Missing application encryption key |
| <a id="lg-config-005"></a>`LG-CONFIG-005` | High | Publicly visible sensitive filesystem defaults |
| <a id="lg-config-006"></a>`LG-CONFIG-006` | Medium | Verbose production logging |
| <a id="lg-config-007"></a>`LG-CONFIG-007` | Medium | Database transport encryption not made explicit |
| <a id="lg-config-008"></a>`LG-CONFIG-008` | Medium | Insecure production mail transport |
| <a id="lg-config-009"></a>`LG-CONFIG-009` | High | Overly broad trusted proxy configuration |

## Routes And Authorization

| Rule | Severity | Detects |
| --- | --- | --- |
| <a id="lg-route-001"></a>`LG-ROUTE-001` | High | Sensitive route missing authentication |
| <a id="lg-route-002"></a>`LG-ROUTE-002` | High | Sensitive route potentially missing authorization |
| <a id="lg-route-003"></a>`LG-ROUTE-003` | Medium | Sensitive route missing rate limiting |
| <a id="lg-route-004"></a>`LG-ROUTE-004` | Critical | Public administrative route |
| <a id="lg-route-005"></a>`LG-ROUTE-005` | High | Sensitive action using GET |
| <a id="lg-route-006"></a>`LG-ROUTE-006` | Medium | Sensitive link potentially missing a signature |
| <a id="lg-route-007"></a>`LG-ROUTE-007` | High | Configured model missing a discoverable policy |

## Uploads

| Rule | Severity | Detects |
| --- | --- | --- |
| <a id="lg-upload-001"></a>`LG-UPLOAD-001` | High | Upload handling potentially missing validation |
| <a id="lg-upload-002"></a>`LG-UPLOAD-002` | High | User-controlled upload filename |
| <a id="lg-upload-003"></a>`LG-UPLOAD-003` | Critical | Dangerous upload extension allowed |
| <a id="lg-upload-004"></a>`LG-UPLOAD-004` | Critical | Executable upload written to public storage |
| <a id="lg-upload-005"></a>`LG-UPLOAD-005` | Medium | Upload validation missing a size limit |
| <a id="lg-upload-006"></a>`LG-UPLOAD-006` | High | User-controlled upload path |
| <a id="lg-upload-007"></a>`LG-UPLOAD-007` | High | SVG upload potentially missing sanitization |
| `LG-UPLOAD-008` | Runtime | Declared and detected MIME mismatch |
| `LG-UPLOAD-009` | Runtime | Executable signature found in uploaded content |
| `LG-UPLOAD-010` | Runtime | Detected MIME is outside the configured allowlist |

## Tenant Isolation

| Rule | Severity | Detects |
| --- | --- | --- |
| <a id="lg-tenant-001"></a>`LG-TENANT-001` | Critical | Tenant model missing a guard constraint |
| <a id="lg-tenant-002"></a>`LG-TENANT-002` | Critical | Potential cross-tenant model access |
| <a id="lg-tenant-003"></a>`LG-TENANT-003` | Critical | Tenant resolver or context missing |
| <a id="lg-tenant-004"></a>`LG-TENANT-004` | Critical | Unscoped tenant bulk update |
| <a id="lg-tenant-005"></a>`LG-TENANT-005` | Critical | Unscoped tenant bulk delete |
| <a id="lg-tenant-006"></a>`LG-TENANT-006` | High | Unscoped raw tenant query |

## Queries

| Rule | Severity | Detects |
| --- | --- | --- |
| <a id="lg-query-001"></a>`LG-QUERY-001` | Critical | Potential SQL injection |
| <a id="lg-query-002"></a>`LG-QUERY-002` | High | Raw SQL without safe binding evidence |
| <a id="lg-query-003"></a>`LG-QUERY-003` | High | Potentially unscoped bulk update |
| <a id="lg-query-004"></a>`LG-QUERY-004` | Critical | Potentially unscoped bulk delete |

## Models

| Rule | Severity | Detects |
| --- | --- | --- |
| <a id="lg-model-001"></a>`LG-MODEL-001` | High | Potentially unsafe mass assignment |
| <a id="lg-model-002"></a>`LG-MODEL-002` | High | Sensitive model attribute potentially serialized |

## Secrets

| Rule | Severity | Detects |
| --- | --- | --- |
| <a id="lg-secret-001"></a>`LG-SECRET-001` | Critical | Potential hardcoded credential |
| <a id="lg-secret-002"></a>`LG-SECRET-002` | Critical | Credential file tracked by Git |

## APIs

| Rule | Severity | Detects |
| --- | --- | --- |
| <a id="lg-api-001"></a>`LG-API-001` | High | API route missing recognized authentication |
| <a id="lg-api-002"></a>`LG-API-002` | Medium | API route missing rate limiting |
| <a id="lg-api-003"></a>`LG-API-003` | Medium | Eloquent result potentially serialized directly |

## Interpreting A Finding

Each finding includes its stable rule ID, module, severity, confidence, description, risk, recommendation, fingerprint, and source location when available. Static matching cannot prove exploitability or absence of a vulnerability. Review the actual data flow and security boundary, then fix the control or record a narrowly scoped suppression with a reason.

Known analysis limitations are documented with the applicable rules and command output.
