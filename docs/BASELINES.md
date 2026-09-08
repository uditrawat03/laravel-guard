# Baseline governance

Laravel Guard baselines let a team accept known findings temporarily without hiding new findings. Schema 4 records ownership, reason, creation and expiration dates, and additional approvers. Policy violations fail closed: `guard:check` does not suppress entries that exceed the configured acceptance policy.

## Create a governed baseline

```bash
php artisan guard:baseline \
  --reason="Reviewed before the current release" \
  --owner="application-security" \
  --approver="security-reviewer" \
  --expires="+30 days"
```

`--reason` is required when findings exist by default. `--owner` defaults to `LARAVEL_GUARD_BASELINE_OWNER`, then the current CI or operating-system user. Repeat `--approver` when policy requires multiple reviewers. `--expires` accepts an ISO date or relative date; when omitted, `baseline_governance.default_ttl_days` is used.

Use `--force` to replace an existing baseline. Schema 1-3 files remain readable. Rewriting an older baseline upgrades it to schema 4 and adds the approver list.

Before writing, the command enforces maximum entry count, maximum TTL, required approval count, allowed severities, reason, and expiration policies. Invalid configuration or a policy violation exits with code 2 and does not write a new baseline.

## Review and maintain

```bash
php artisan guard:baseline --list
php artisan guard:baseline --explain=LG-UPLOAD-001
php artisan guard:baseline --explain=<fingerprint>
php artisan guard:baseline --prune
```

Listing shows status, owner, reason, and expiration. Explanation accepts an exact fingerprint or rule ID. Pruning rescans the application and removes entries that have expired or no longer match a current finding. `guard:doctor --strict` reports malformed policy, legacy schema, expired entries, and policy violations.

`guard:check` evaluates the complete baseline document against current policy before applying any fingerprints. If one or more entries violate policy, none are trusted for that run and the original findings remain visible.

## Configuration

```php
'baseline_governance' => [
    'require_reason' => true,
    'require_expiration' => true,
    'default_ttl_days' => 90,
    'max_ttl_days' => 90,
    'max_entries' => 500,
    'required_approvals' => 1,
    'allowed_severities' => ['low', 'medium', 'high', 'critical'],
    'owner' => env('LARAVEL_GUARD_BASELINE_OWNER'),
],
```

To prohibit acceptance of critical findings, remove `critical` from `allowed_severities`. Set `required_approvals` above one and supply additional `--approver` values for higher-risk environments. A zero `max_ttl_days` disables the maximum only when expiration remains otherwise governed; disabling expiration should be a deliberate local policy decision.

Commit the baseline with the application when CI should share the same accepted-debt boundary. Review baseline changes like source code: a changed fingerprint, approver, expiration, or new entry changes which security findings CI allows.