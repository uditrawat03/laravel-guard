<?php

namespace LaravelGuard\Integrations\Telescope;

use Illuminate\Contracts\Events\Dispatcher;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Scoring\SecurityScore;
use LaravelGuard\Integrations\Contracts\FindingIntegration;
use LaravelGuard\Integrations\Events\SecurityScanCompleted;

final readonly class TelescopeIntegration implements FindingIntegration
{
    public function __construct(private Dispatcher $events) {}

    public function name(): string
    {
        return 'telescope';
    }

    public function available(): bool
    {
        return class_exists('Laravel\\Telescope\\Telescope');
    }

    public function publish(FindingCollection $findings): void
    {
        if ($this->available()) {
            $score = SecurityScore::fromFindings($findings);
            $this->events->dispatch(new SecurityScanCompleted($findings->count(), $findings->counts(), $score->score));
        }
    }
}
