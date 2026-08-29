# Mutation testing

Laravel Guard uses Infection to check whether its tests detect intentional changes to package behavior. This complements line coverage: executing a line does not prove that an assertion protects its security decision.

## Current budget

The full `src` catalog is tested with Infection 0.35 on PHP 8.3. The latest hosted hardening run generated 2,811 mutations:

| Result | Count |
|---|---:|
| Killed by tests | 1,265 |
| Errored | 1 |
| Escaped | 1,545 |
| Timed out | 0 |
| Not covered | 0 |

MSI and covered MSI are both 45.04%, up from the original 43.19% baseline across a smaller 2,204-mutant catalog. `infection.json5` enforces a 45% floor for both metrics so the measured improvement cannot regress silently. The first hardening slice added boundary coverage for security scoring, finding identity, serialization, collection thresholds, and fingerprint filtering. This is a milestone, not the quality target. Before v1, the package still aims for at least 70% MSI and 80% covered MSI, with security-relevant survivors either killed by tests or documented as equivalent or unproductive mutations.

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

The latest run retains 100% mutation code coverage and improves MSI to 45.04%. The surviving mutants remain a documented quality backlog until equivalent or stronger tests kill them.
