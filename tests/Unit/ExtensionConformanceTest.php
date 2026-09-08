<?php

namespace LaravelGuard\Tests\Unit;

use LaravelGuard\Core\Contracts\GuardRule;
use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Contracts\SecurityReporter;
use LaravelGuard\Core\Extensions\ExtensionApi;
use LaravelGuard\Core\Extensions\ExtensionConformance;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\RuleRegistry;
use LaravelGuard\Integrations\Contracts\FindingIntegration;
use LaravelGuard\Tests\TestCase;

final class ExtensionConformanceTest extends TestCase
{
    public function test_extension_api_versions_are_stable_and_discoverable(): void
    {
        $this->assertSame('1.0', ExtensionApi::VERSION);
        $this->assertSame(1, ExtensionApi::CONFIGURATION_VERSION);
        $this->assertSame(4, ExtensionApi::outputSchemas()['laravel-guard/baseline']);
    }

    public function test_rule_conformance_accepts_well_formed_findings(): void
    {
        $rule = $this->rule();

        ExtensionConformance::assertRule($rule, $this->app->make(SecurityContext::class));
        $this->addToAssertionCount(1);
    }

    public function test_rule_conformance_rejects_invalid_definitions_and_results(): void
    {
        $invalid = $this->rule(id: 'custom rule', category: 'Custom Rules');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('uppercase namespaced form');

        ExtensionConformance::assertRuleDefinition($invalid);
    }

    public function test_rule_conformance_rejects_a_mismatched_finding_identity(): void
    {
        $rule = $this->rule(findingRuleId: 'ACME-AUTH-999');
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('emitted finding ID');

        ExtensionConformance::assertRule($rule, $this->app->make(SecurityContext::class));
    }

    public function test_reporter_and_integration_conformance_execute_public_contracts(): void
    {
        $reporter = new class implements SecurityReporter
        {
            public function render(FindingCollection $findings): string
            {
                return json_encode(['findings' => $findings->count()], JSON_THROW_ON_ERROR);
            }
        };
        $integration = new class implements FindingIntegration
        {
            public bool $published = false;

            public function name(): string
            {
                return 'test-integration';
            }

            public function available(): bool
            {
                return true;
            }

            public function publish(FindingCollection $findings): void
            {
                $this->published = true;
            }
        };

        ExtensionConformance::assertReporter($reporter);
        ExtensionConformance::assertIntegration($integration);

        $this->assertTrue($integration->published);
    }

    public function test_rule_registry_rejects_duplicate_ids(): void
    {
        $registry = $this->app->make(RuleRegistry::class);
        $existing = $registry->all()[0];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('already registered');

        $registry->register($existing);
    }

    private function rule(
        string $id = 'ACME-AUTH-001',
        string $category = 'custom-auth',
        string $findingRuleId = 'ACME-AUTH-001',
    ): GuardRule {
        return new class($id, $category, $findingRuleId) implements GuardRule
        {
            public function __construct(
                private readonly string $ruleId,
                private readonly string $ruleCategory,
                private readonly string $findingRuleId,
            ) {}

            public function id(): string
            {
                return $this->ruleId;
            }

            public function name(): string
            {
                return 'Custom authorization rule';
            }

            public function description(): string
            {
                return 'Detects a custom authorization boundary.';
            }

            public function category(): string
            {
                return $this->ruleCategory;
            }

            public function severity(): Severity
            {
                return Severity::High;
            }

            public function scan(SecurityContext $context): iterable
            {
                yield new SecurityFinding(
                    $this->findingRuleId,
                    $this->ruleCategory,
                    Severity::High,
                    Confidence::High,
                    'Custom authorization rule',
                    'A boundary is missing.',
                    'Unauthorized access is possible.',
                    'Add an authorization check.',
                );
            }
        };
    }
}
