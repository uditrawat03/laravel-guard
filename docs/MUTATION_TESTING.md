# Mutation testing

Laravel Guard uses Infection to check whether its tests detect intentional changes to package behavior. This complements line coverage: executing a line does not prove that an assertion protects its security decision.

## V1 budget

The full `src` catalog is tested with Infection 0.35 on PHP 8.3. The v1 candidate run generated 3,232 mutations:

| Result | Count |
|---|---:|
| Killed by tests | 2,361 |
| Errored | 0 |
| Escaped | 871 |
| Timed out | 0 |
| Not covered | 0 |

MSI and covered MSI are both 73.05%, up from the published 45.04% milestone. They are equal because mutation code coverage is 100%. `infection.json5` enforces a 70% floor for both metrics. The v1 hardening includes rule matching, API boundaries, suppressions, fingerprinting, severity thresholds, tenant enforcement, baseline governance, schemas, extension contracts, Git diff behavior, diagnostics, and dashboard authorization.

### Survivor audit

The 871 escaped mutants are retained in the detailed CI artifact and grouped for review. The v1 candidate contained 312 command-layer and 192 report-rendering survivors, largely changes to labels, casts, concatenation, and optional output fields. Security-relevant survivor groups were smaller and explicitly remain regression targets: 75 baseline, 65 route, 50 Git diff, 41 diagnostics, 27 upload, 24 configuration, 20 extension-conformance, 18 tenant, 14 scoped-exception, 12 runtime, 9 suppression, and 5 finding mutations. API, query, model, secret, and integration rules had no survivors in this run; the UI had one.

The 70% full-catalog floor is the v1 release gate. Raising both scores to 80% is a post-v1 hardening objective, prioritizing the security-relevant groups above before presentation-only mutations.

## Running locally

Install the development dependencies and run:

```bash
composer install
mkdir -p build
vendor/bin/infection --show-mutations=0 --log-verbosity=default
```

On Windows PowerShell, create the report directory with `New-Item -ItemType Directory -Force build` before running `composer exec infection -- --show-mutations=0 --log-verbosity=default`.

The configured 15-second mutant timeout is treated as an escaped mutation, and any timeout fails the run through `maxTimeouts: 0`. Threshold failures also return a non-zero exit code.

## CI and reports

The `Mutation Budget` workflow runs every Monday and can be dispatched manually. It uses PCOV, has a 30-minute job ceiling, tests the complete source catalog, and uploads:

- `infection.log`: detailed mutation results and diffs.
- `infection-summary.log`: human-readable totals.
- `infection-summary.json`: machine-readable totals and scores.
- `infection-per-mutator.md`: results grouped by mutator.

Use the detailed and per-mutator reports to prioritize security-critical behavior first: tenant constraints, authorization recognition, suppression targeting, fingerprints, severity thresholds, secret redaction, and runtime upload rejection.

## Interpreting the scores

- **MSI** is the percentage of all generated mutations killed or otherwise detected.
- **Covered MSI** applies the same calculation to mutations reached by the test suite.
- **Mutation code coverage** indicates whether tests execute mutated code; it does not show whether assertions reject the mutation.

The v1 candidate retains 100% mutation code coverage and improves MSI to 73.05%. Surviving security-relevant mutants remain documented hardening work even though the enforced v1 floor is satisfied.
