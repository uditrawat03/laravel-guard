# Performance

Use the static benchmark inside a representative Laravel application:

```bash
php artisan guard:benchmark --runs=10
```

The first scan is reported as cold. Remaining scans produce the warm average and nearest-rank P95. The command also reports absolute process peak memory. Repeat `--path` to benchmark an isolated source tree without changing `laravel-guard.paths`:

```bash
php artisan guard:benchmark \
  --runs=10 \
  --path=app \
  --path=routes \
  --max-p95-ms=500 \
  --max-memory-mb=128
```

A breached ceiling prints every violation and returns a failing exit code. Use `--format=json` for a single machine-readable record. The output contract is `laravel-guard/performance` version 1 and its JSON Schema is packaged at `resources/schemas/performance-v1.json`.

## Runtime hooks

Measure the tenant-query listener, upload middleware, or scoped state lifecycle in timed batches:

```bash
php artisan guard:benchmark-runtime query --runs=10 --operations=20000
php artisan guard:benchmark-runtime upload --runs=10 --operations=500
php artisan guard:benchmark-runtime worker --runs=10 --operations=1000 --max-memory-growth-mb=16
```

Use `--max-p95-us`, `--max-memory-mb`, and `--max-memory-growth-mb` to enforce runtime ceilings. P95 is calculated across batch averages and reported in microseconds per operation. Every report includes retained memory growth and a state-leak count. JSON output follows `laravel-guard/runtime-performance` version 2, packaged at `resources/schemas/runtime-performance-v2.json`; the v1 schema remains packaged for existing consumers.

The query scenario uses an active tenant and a safely constrained query through the same listener closure that calls `TenantQueryInspector`. The upload scenario sends a request containing a valid PNG through `InspectUploadedFiles`, including file flattening, MIME/signature checks, executable-marker inspection, and the downstream middleware callback. The worker scenario records an event, scans it through the persistent rule registry, flushes Laravel scoped instances as Octane and queue workers do between cycles, and verifies that the next collector is empty. Any stale or missing event increments `state_leaks` and fails the command. These scenarios isolate package hook cost; they do not include database execution, HTTP transport, controller work, or application storage.

## Package budgets

The public [Performance Budget workflow](https://github.com/uditrawat03/laravel-guard/actions/runs/32690766068) uses Laravel 13 and PHP 8.3. Deterministic Laravel-shaped fixtures enforce:

| Fixture | Files | Cold | Warm P95 | Peak memory | Enforced ceiling |
|---|---:|---:|---:|---:|---:|
| Small | 25 | 41.140 ms | 23.561 ms | 30 MB | 750 ms / 128 MB |
| Medium | 250 | 203.175 ms | 124.184 ms | 44 MB | 2,000 ms / 192 MB |
| Large | 1,000 | 1,836.818 ms | 1,123.051 ms | 92 MB | 5,000 ms / 256 MB |

The same run enforces runtime budgets:

| Scenario | Operations per batch | Average | P95 | Peak memory | P95 ceiling |
|---|---:|---:|---:|---:|---:|
| Tenant query listener | 20,000 | 0.477 us/op | 0.483 us/op | 28 MB | 50 us/op |
| Upload middleware | 500 | 29.852 us/op | 30.230 us/op | 28 MB | 1,000 us/op |
| Worker scope lifecycle | 1,000 | Pending public run | Pending public run | Pending public run | 2,000 us/op / 16 MB growth |

Every matrix job uploads its JSON report and fails closed through shell `pipefail`. A per-scan source snapshot prevents rules from repeatedly enumerating and reading the same files while ensuring a later scan receives a fresh snapshot.

These are synthetic package regression ceilings, not promises for consumer applications. Source complexity, route count, enabled modules, storage speed, PHP extensions, and optional integrations affect results. Consumers should establish budgets from their own stable CI runners and production-shaped fixtures.

The worker lifecycle check covers Laravel scoped event collectors held behind persistent rule instances. Broader production-shaped Octane validation for tenant transitions, uploads, third-party integrations, and application-owned singleton services remains future work.
