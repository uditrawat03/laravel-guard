# Security policy

## Supported versions

Security fixes are provided for the latest stable release. Until v1.0, users should track the latest tagged pre-release because rule and extension APIs may change.

## Reporting a vulnerability

Do not open a public issue for a vulnerability in Laravel Guard. Send a private report to the maintainers with:

- affected version and Laravel/PHP versions;
- impact and realistic exploitation conditions;
- a minimal reproducer or failing test;
- whether the issue could expose secrets from scanned applications.

Maintainers should acknowledge a complete report within three business days, provide an assessment within ten business days, and coordinate disclosure after a fix is available. Never include real application credentials or customer data in a report.

## Scanner data handling

Laravel Guard runs locally by default. Reports can contain file paths, route names, model names, and normalized source snippets. Secret findings mask values and must never serialize the complete credential. Review reports before uploading them to third-party systems.
