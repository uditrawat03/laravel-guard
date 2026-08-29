<?php

namespace LaravelGuard\Tests\Unit;

use LaravelGuard\Core\Rules\RuleReference;
use LaravelGuard\LaravelGuard;
use LaravelGuard\Tests\TestCase;

final class RuleReferenceTest extends TestCase
{
    public function test_every_builtin_rule_has_guidance_and_a_documented_anchor(): void
    {
        $documentation = file_get_contents(dirname(__DIR__, 2).'/docs/RULES.md');

        foreach ($this->app->make(LaravelGuard::class)->rules() as $rule) {
            $reference = RuleReference::for($rule);
            $this->assertNotSame('', $reference['why_it_matters']);
            $this->assertNotSame('', $reference['how_to_respond']);
            $this->assertSame('https://github.com/uditrawat03/laravel-guard/blob/main/docs/RULES.md#'.strtolower($rule->id()), $reference['documentation_url']);
            $this->assertStringContainsString('id="'.strtolower($rule->id()).'"', $documentation);
        }
    }

    public function test_documentation_url_supports_safe_templates_and_rejects_unsafe_schemes(): void
    {
        config()->set('laravel-guard.documentation_url', 'https://security.example/rules/{RULE}');
        $this->assertSame('https://security.example/rules/LG-TEST-001', RuleReference::documentationUrl('LG-TEST-001'));

        config()->set('laravel-guard.documentation_url', 'javascript:alert(1)');
        $this->assertSame(
            'https://github.com/uditrawat03/laravel-guard/blob/main/docs/RULES.md#lg-test-001',
            RuleReference::documentationUrl('LG-TEST-001'),
        );
    }
}
