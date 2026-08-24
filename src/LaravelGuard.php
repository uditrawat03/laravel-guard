<?php

namespace LaravelGuard;

use LaravelGuard\Core\Contracts\GuardRule;
use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Exceptions\SecurityExceptionManager;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Rules\RuleRegistry;
use LaravelGuard\Core\Scanner;
use LaravelGuard\Integrations\IntegrationManager;

final readonly class LaravelGuard
{
    public function __construct(
        private Scanner $scanner,
        private RuleRegistry $rules,
        private SecurityContext $context,
        private SecurityExceptionManager $exceptions,
        private IntegrationManager $integrations,
    ) {}

    /** @param list<string>|null $paths */
    public function scan(?string $module = null, ?array $paths = null): FindingCollection
    {
        $config = $this->context->config;
        if ($paths !== null) {
            $config['paths'] = $paths;
        }
        $findings = $this->scanner->scan(new SecurityContext($this->context->app, $config, $module));
        $this->integrations->publish($findings);

        return $findings;
    }

    public function registerRule(string|GuardRule $rule): self
    {
        $this->rules->register($rule);

        return $this;
    }

    public function rules(): array
    {
        return $this->rules->all();
    }

    public function allow(string $rule, string $reason, callable $callback): mixed
    {
        return $this->exceptions->allow($rule, $reason, $callback);
    }

    public function exceptionAudit(): array
    {
        return $this->exceptions->audit();
    }
}
