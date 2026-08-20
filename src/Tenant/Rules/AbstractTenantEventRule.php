<?php

namespace LaravelGuard\Tenant\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Runtime\SecurityEventCollector;

abstract class AbstractTenantEventRule extends AbstractGuardRule
{
    public function __construct(private readonly SecurityEventCollector $events) {}

    public function category(): string
    {
        return 'tenant';
    }

    public function scan(SecurityContext $context): iterable
    {
        foreach ($this->events->forRule($this->id()) as $event) {
            yield SecurityFinding::fromRule($this, $event->message, $this->risk(), $this->recommendation(), Confidence::High, $event->file, $event->line, $event->metadata);
        }
    }

    abstract protected function risk(): string;

    abstract protected function recommendation(): string;
}
