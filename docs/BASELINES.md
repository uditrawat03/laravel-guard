# Baseline governance

Laravel Guard baselines let a team accept known findings temporarily without hiding new findings. Schema 3 records who owns each acceptance, why it was accepted, when it was created, and when it expires.

## Create a governed baseline

```bash
php artisan guard:baseline \
  --reason="Reviewed before the current release" \
  --owner="application-security" \
  --expires="+90 days"
```

`--reason` is required when findings exist by default. `--owner` defaults to `LARAVEL_GUARD_BASELINE_OWNER`, then the current CI or operating-system user. `--expires` accepts an ISO date or a relative date. When omitted, `baseline_governance.default_ttl_days` is used.

Use `--force` to replace an existing baseline. Schema 1 and 2 files remain readable, but they have no acceptance metadata and Doctor reports non-empty legacy files as a warning.

## Review and maintain

```bash
php artisan guard:baseline --list
php artisan guard:baseline --explain=LG-UPLOAD-001
php artisan guard:baseline --explain=<fingerprint>
php artisan guard:baseline --prune
```

Listing shows status, owner, reason, and expiration. Explanation accepts an exact fingerprint or rule ID. Pruning rescans the application and removes entries that have expired or no longer match a current finding.

Expired entries never suppress `guard:check`. They resurface as ordinary findings and console output reports that the acceptance expired. `guard:doctor --strict` also fails while expired entries, legacy governed entries, or missing required reasons need review.

## Configuration

```php
'baseline_governance' => [
    'require_reason' => true,
    'default_ttl_days' => 90, // Set to 0 for no default expiration.
    'owner' => env('LARAVEL_GUARD_BASELINE_OWNER'),
],
```

Commit the baseline with the application when CI should share the same accepted-debt boundary. Review baseline changes like source code: a changed fingerprint or new entry changes which security findings CI allows.
