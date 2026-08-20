<?php

namespace LaravelGuard\Core\Contracts;

use LaravelGuard\Core\Findings\FindingCollection;

interface SecurityReporter
{
    public function render(FindingCollection $findings): string;
}
