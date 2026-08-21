<?php

namespace LaravelGuard\Integrations\Events;

final readonly class SecurityScanCompleted
{
    public function __construct(public int $total, public array $counts, public int $score) {}
}
