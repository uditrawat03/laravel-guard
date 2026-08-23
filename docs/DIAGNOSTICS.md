# Configuration diagnostics

`guard:doctor` validates Laravel Guard before a scan or CI workflow depends on it.

```bash
php artisan guard:doctor
php artisan guard:doctor --strict
php artisan guard:doctor --format=json
php artisan guard:doctor --output=storage/app/security/guard.sarif
```

Errors always return a failing exit code. Warnings fail only with `--strict`. `--output` does not write a report; it verifies that the requested report filename is not a directory and that its existing file or parent directory is writable.

Current diagnostics cover:

- severity thresholds, modules, scan paths, and runtime environments;
- tenant columns, resolvers, and configured tenant models;
- custom reporters, custom-rule boot errors, and optional integrations;
- upload MIME allowlists and required runtime support;
- baseline location, schema, reasons, expiration, and governance settings;
- suppression rule IDs, target structure, global scope, and structured reasons;
- configured policy-model class existence and Eloquent inheritance;
- Git executable availability and whether the application is in a worktree;
- an explicitly requested report output destination.

Warnings identify configurations that work but deserve review, including global suppressions, unknown rule IDs, expired baseline entries, and missing Git context. Use strict mode in CI to turn these review items into a required decision.
