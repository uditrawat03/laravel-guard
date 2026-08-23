# Performance

Use the benchmark command inside a representative Laravel application:

```bash
php artisan guard:benchmark --runs=10
```

The first scan is reported as cold. Remaining scans produce the warm average and nearest-rank P95. The command also reports absolute process peak memory.

Enforce project-specific budgets in CI:

```bash
php artisan guard:benchmark \
  --runs=10 \
  --max-p95-ms=500 \
  --max-memory-mb=128
```

A breached ceiling prints every violation and returns a failing exit code. Use `--format=json` for a single machine-readable record. The output contract is `laravel-guard/performance` version 1 and its JSON Schema is packaged at `resources/schemas/performance-v1.json`.

## Package budget

The public [Performance Budget workflow](https://github.com/uditrawat03/laravel-guard/actions/runs/32645261893) runs ten scans on Laravel 13 and PHP 8.3 and enforces:

- Warm P95 at or below 500 ms.
- Peak memory at or below 128 MB.

The first passing hosted result measured 52.147 ms cold, 41.982 ms warm P95, and 30 MB peak memory. The workflow uploads the complete JSON report for comparison and fails closed through shell `pipefail` when the benchmark rejects a regression.

These are package-fixture regression ceilings, not promises for consumer applications. Source count, route count, enabled modules, storage speed, and optional integrations affect results. Consumers should establish budgets from their own stable CI runners.

The source index caches parsed content within a scan, and runtime inspection remains opt-in. Representative medium/large application fixtures, query/upload hook overhead, and Octane retained-state measurements are still pending.
