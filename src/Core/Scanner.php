<?php

namespace LaravelGuard\Core;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Diagnostics\ConfigurationIssueBag;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Rules\RuleRegistry;
use LaravelGuard\Core\Suppressions\ConfigSuppressionResolver;

final readonly class Scanner
{
    public function __construct(
        private RuleRegistry $rules,
        private ConfigSuppressionResolver $suppressions,
        private ConfigurationIssueBag $configurationIssues,
    ) {}

    public function scan(SecurityContext $context): FindingCollection
    {
        if ($this->configurationIssues->hasErrors()) {
            throw new \RuntimeException('Laravel Guard configuration is invalid. Run [php artisan guard:doctor] for details.');
        }

        $findings = new FindingCollection;
        if (! ($context->config['enabled'] ?? true)) {
            return $findings;
        }

        foreach ($this->rules->all() as $rule) {
            if ($context->module && $rule->category() !== $context->module) {
                continue;
            }

            if (! ($context->config['modules'][$rule->category()] ?? true)) {
                continue;
            }

            foreach ($rule->scan($context) as $finding) {
                if ($finding instanceof SecurityFinding && ! $this->suppressions->suppressed($finding, $context)) {
                    $findings->add($finding);
                }
            }
        }

        return $findings;
    }
}
