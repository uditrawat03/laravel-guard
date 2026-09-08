<?php

namespace LaravelGuard\Core\Extensions;

use LaravelGuard\Core\Contracts\GuardRule;
use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Contracts\SecurityReporter;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Integrations\Contracts\FindingIntegration;

final class ExtensionConformance
{
    public static function assertRuleDefinition(GuardRule $rule): void
    {
        $errors = self::ruleDefinitionErrors($rule);
        if ($errors !== []) {
            throw new \InvalidArgumentException('Invalid Laravel Guard rule ['.$rule::class.']: '.implode(' ', $errors));
        }
    }

    public static function assertRule(GuardRule $rule, SecurityContext $context): void
    {
        self::assertRuleDefinition($rule);

        foreach ($rule->scan($context) as $index => $finding) {
            if (! $finding instanceof SecurityFinding) {
                throw new \UnexpectedValueException("Rule [{$rule->id()}] result [{$index}] must be a SecurityFinding.");
            }
            if ($finding->ruleId !== $rule->id()) {
                throw new \UnexpectedValueException("Rule [{$rule->id()}] emitted finding ID [{$finding->ruleId}].");
            }
            if ($finding->category !== $rule->category()) {
                throw new \UnexpectedValueException("Rule [{$rule->id()}] emitted category [{$finding->category}] instead of [{$rule->category()}].");
            }
        }
    }

    public static function assertReporter(SecurityReporter $reporter, ?FindingCollection $findings = null): void
    {
        $output = $reporter->render($findings ?? new FindingCollection);
        if (trim($output) === '') {
            throw new \UnexpectedValueException('Reporter ['.$reporter::class.'] must return non-empty output for an empty collection.');
        }
    }

    public static function assertIntegration(FindingIntegration $integration, ?FindingCollection $findings = null): void
    {
        if (trim($integration->name()) === '') {
            throw new \InvalidArgumentException('Finding integration ['.$integration::class.'] must have a non-empty name.');
        }

        if ($integration->available()) {
            $integration->publish($findings ?? new FindingCollection);
        }
    }

    /** @return list<string> */
    public static function ruleDefinitionErrors(GuardRule $rule): array
    {
        $errors = [];
        if (preg_match('/^[A-Z][A-Z0-9]*(?:-[A-Z0-9]+)+-[0-9]{3}$/', $rule->id()) !== 1) {
            $errors[] = 'The ID must use an uppercase namespaced form such as ACME-AUTH-001.';
        }
        foreach (['name' => $rule->name(), 'description' => $rule->description(), 'category' => $rule->category()] as $field => $value) {
            if (trim($value) === '') {
                $errors[] = "The {$field} must not be empty.";
            }
        }
        if (preg_match('/^[a-z][a-z0-9-]*$/', $rule->category()) !== 1) {
            $errors[] = 'The category must use lowercase kebab-case.';
        }

        return $errors;
    }
}
