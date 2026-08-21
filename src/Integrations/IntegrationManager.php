<?php

namespace LaravelGuard\Integrations;

use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Integrations\Contracts\FindingIntegration;
use LaravelGuard\Integrations\Debugbar\DebugbarIntegration;
use LaravelGuard\Integrations\Telescope\TelescopeIntegration;

final readonly class IntegrationManager
{
    public function __construct(private TelescopeIntegration $telescope, private DebugbarIntegration $debugbar) {}

    public function publish(FindingCollection $findings): void
    {
        foreach ($this->integrations() as $integration) {
            if (config("laravel-guard.integrations.{$integration->name()}", false)) {
                $integration->publish($findings);
            }
        }
    }

    /** @return list<FindingIntegration> */
    public function integrations(): array
    {
        return [$this->telescope, $this->debugbar];
    }

    public function status(): array
    {
        return array_map(fn (FindingIntegration $integration) => [
            'name' => $integration->name(),
            'available' => $integration->available(),
            'enabled' => (bool) config("laravel-guard.integrations.{$integration->name()}", false),
        ], $this->integrations());
    }
}
