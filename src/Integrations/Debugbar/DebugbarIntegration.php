<?php

namespace LaravelGuard\Integrations\Debugbar;

use Illuminate\Contracts\Foundation\Application;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Scoring\SecurityScore;
use LaravelGuard\Integrations\Contracts\FindingIntegration;

final readonly class DebugbarIntegration implements FindingIntegration
{
    public function __construct(private Application $app) {}

    public function name(): string
    {
        return 'debugbar';
    }

    public function available(): bool
    {
        return class_exists('Barryvdh\\Debugbar\\LaravelDebugbar') && $this->app->bound('debugbar');
    }

    public function publish(FindingCollection $findings): void
    {
        if (! $this->available()) {
            return;
        }
        $debugbar = $this->app->make('debugbar');
        if (method_exists($debugbar, 'addMessage')) {
            $score = SecurityScore::fromFindings($findings);
            $debugbar->addMessage(['total' => $findings->count(), 'counts' => $findings->counts(), 'score' => $score->score], 'laravel-guard');
        }
    }
}
