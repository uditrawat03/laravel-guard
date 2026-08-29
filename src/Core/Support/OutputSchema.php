<?php

namespace LaravelGuard\Core\Support;

final class OutputSchema
{
    public const REPORT = 'laravel-guard/report';

    public const REPORT_VERSION = 1;

    public const DIFF = 'laravel-guard/diff';

    public const DIFF_VERSION = 1;

    public const BASELINE = 'laravel-guard/baseline';

    public const BASELINE_VERSION = 3;

    public const JUNIT = 'laravel-guard/junit';

    public const JUNIT_VERSION = 1;

    public const PERFORMANCE = 'laravel-guard/performance';

    public const PERFORMANCE_VERSION = 1;

    public const RUNTIME_PERFORMANCE = 'laravel-guard/runtime-performance';

    public const RUNTIME_PERFORMANCE_VERSION = 2;

    private function __construct() {}
}
