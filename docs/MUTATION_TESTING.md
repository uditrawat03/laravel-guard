# Mutation testing

Laravel Guard uses Infection to check whether its tests detect intentional changes to package behavior. This complements line coverage: executing a line does not prove that an assertion protects its security decision.

## V1 budget

The full `src` catalog is tested with Infection 0.35 on PHP 8.3. The hosted v1 candidate run generated 3,231 mutations:

| Result | Count |
|---|---:|
| Killed by tests | 1,492 |
| Errored | 1 |
| Escaped | 1,738 |
| Timed out | 0 |
| Not covered | 0 |

MSI and covered MSI are both 46.21%. They are equal because mutation code coverage is 100%. `infection.json5` enforces a 45% regression floor for both metrics. This is the reproducible hosted baseline used for the v1 gate; it does not imply that the surviving mutants are complete or acceptable as a long-term quality target.

### Survivor audit

The 1,738 escaped mutants are retained in the detailed CI artifact for review. The hosted summary also contains one errored mutant; the workflow now uses full log verbosity so its exact location and failure can be captured on the next run. Survivor classifications must be regenerated from that hosted report before making subsystem-specific claims.

The 45% full-catalog floor is the v1 regression gate. Raising both scores to 70% is a post-v1 hardening objective, prioritizing security-relevant behavior before presentation-only mutations.

## Running locally

Install the development dependencies and run:

```bash
composer install
mkdir -p build
vendor/bin/infection --show-mutations=0 --log-verbosity=all
```

On Windows PowerShell, create the report directory with `New-Item -ItemType Directory -Force build` before running `composer exec infection -- --show-mutations=0 --log-verbosity=all`.

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

The hosted v1 candidate retains 100% mutation code coverage and measures 46.21% MSI. Surviving mutants and the single errored mutant remain documented hardening work even though the enforced 45% v1 regression floor is satisfied.
