# Performance

Use `php artisan guard:benchmark --runs=10` inside a representative Laravel application. Record cold and warm scan timings in pull requests that change parsing, indexing, or rule traversal.

Performance changes should be evaluated against a real application because source count, route count, and enabled modules dominate scan cost. The source index caches parsed content within a scan, and runtime inspection remains opt-in.
