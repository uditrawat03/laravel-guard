<?php

namespace LaravelGuard\Core\Reporting;

use Illuminate\Contracts\Container\Container;
use LaravelGuard\Core\Contracts\SecurityReporter;
use LaravelGuard\Core\Findings\FindingCollection;

final readonly class ReportManager
{
    public function __construct(
        private Container $container,
        private JsonReporter $json,
        private SarifReporter $sarif,
        private GithubReporter $github,
        private JunitReporter $junit,
        private HtmlReporter $html,
        private LogReporter $log,
    ) {}

    public function render(string $format, FindingCollection $findings): string
    {
        $reporter = match (strtolower($format)) {
            'json' => $this->json,
            'sarif' => $this->sarif,
            'github' => $this->github,
            'junit' => $this->junit,
            'html' => $this->html,
            'log' => $this->log,
            default => $this->custom($format),
        };

        return $reporter->render($findings);
    }

    private function custom(string $format): SecurityReporter
    {
        $class = config("laravel-guard.reporters.{$format}");
        if (! is_string($class)) {
            throw new \InvalidArgumentException("Unsupported report format [{$format}].");
        }
        $reporter = $this->container->make($class);
        if (! $reporter instanceof SecurityReporter) {
            throw new \InvalidArgumentException("Reporter [{$class}] must implement SecurityReporter.");
        }

        return $reporter;
    }
}
