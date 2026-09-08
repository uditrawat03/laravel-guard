<?php

namespace LaravelGuard\Core\Rules;

use Illuminate\Contracts\Container\Container;
use LaravelGuard\Core\Contracts\GuardRule;
use LaravelGuard\Core\Extensions\ExtensionConformance;

final class RuleRegistry
{
    private array $rules = [];

    public function __construct(private readonly Container $container) {}

    public function register(string|GuardRule $rule): self
    {
        $instance = is_string($rule) ? $this->container->make($rule) : $rule;
        if (! $instance instanceof GuardRule) {
            throw new \InvalidArgumentException('Guard rules must implement GuardRule.');
        }
        ExtensionConformance::assertRuleDefinition($instance);
        if (isset($this->rules[$instance->id()])) {
            $existing = $this->rules[$instance->id()]::class;

            throw new \InvalidArgumentException("Guard rule [{$instance->id()}] is already registered by [{$existing}].");
        }
        $this->rules[$instance->id()] = $instance;

        return $this;
    }

    public function all(): array
    {
        return array_values($this->rules);
    }
}
