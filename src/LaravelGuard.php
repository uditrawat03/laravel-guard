<?php

namespace LaravelGuard;

use LaravelGuard\Core\Contracts\GuardRule;
use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Rules\RuleRegistry;
use LaravelGuard\Core\Scanner;

final readonly class LaravelGuard
{
    public function __construct(private Scanner $scanner, private RuleRegistry $rules, private SecurityContext $context) {}

    public function scan(?string $module = null): FindingCollection
    {
        return $this->scanner->scan(new SecurityContext($this->context->app, $this->context->config, $module));
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
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('A security exception requires a reason.');
        }

return $callback();
    }
}
