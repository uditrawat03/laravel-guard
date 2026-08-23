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
            $this->assertStringContainsString('id="'.strtolower($rule->id()).'"', $documentation);
        }
    }
}
