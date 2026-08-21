<?php

namespace LaravelGuard\Integrations\Contracts;

use LaravelGuard\Core\Findings\FindingCollection;

interface FindingIntegration
{
    public function name(): string;

    public function available(): bool;

    public function publish(FindingCollection $findings): void;
}
