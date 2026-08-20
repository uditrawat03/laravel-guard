<?php

namespace LaravelGuard\Core\Contracts;

use LaravelGuard\Core\Findings\Severity;

interface GuardRule
{
    public function id(): string;

    public function name(): string;

    public function description(): string;

    public function category(): string;

    public function severity(): Severity;

    public function scan(SecurityContext $context): iterable;
}
