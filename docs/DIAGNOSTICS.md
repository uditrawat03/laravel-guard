# Configuration diagnostics

`guard:doctor` validates Laravel Guard and its local operating environment before a scan or CI workflow depends on it.

```bash
php artisan guard:doctor
php artisan guard:doctor --strict
php artisan guard:doctor --format=json
php artisan guard:doctor --output=storage/app/security/guard.sarif
php artisan guard:doctor --connectivity
```

Errors always return a failing exit code. Warnings fail only with `--strict`. `--output` does not write a report; it verifies that the requested report filename is not a directory and that its existing file or parent directory is writable.

The default command performs no external network or service probe. `--connectivity` explicitly performs bounded database, cache, queue, and default-filesystem round trips. Probe exceptions are reduced to their class name; messages, endpoints, queries, and credentials are not printed.

Current diagnostics cover:

- severity thresholds, modules, scan paths, and runtime environments;
- tenant columns, resolvers, and configured tenant models;
- custom reporters, custom-rule boot errors, stable extension definitions, and optional integrations;
- upload MIME allowlists and required runtime support;
- baseline location, schema, reasons, expiration, approvers, entry limits, TTL, approval count, and severity policy;
- suppression rule IDs, target structure, global scope, and structured reasons;
- configured policy-model class existence and Eloquent inheritance;
- database, cache, queue, and filesystem default driver configuration;
- storage and package UI runtime-path writability;
- scoped runtime event-state registration and the worker benchmark command used for leak enforcement;
- Git executable, worktree, shallow-checkout, and minimum-history context;
- an explicitly requested report output destination;
- opt-in database, cache, queue, and filesystem connectivity.

Warnings identify configurations that work but deserve review, including global suppressions, unknown rule IDs, expired baseline entries, shallow or missing Git history, and missing Git context. Use strict mode in CI to turn these review items into a required decision.

Live probes may create a short-lived cache key and filesystem object, then remove it. Database probing opens the configured connection; queue probing asks the driver for its current size. Run connectivity probes with the same least-privilege credentials used by the application.