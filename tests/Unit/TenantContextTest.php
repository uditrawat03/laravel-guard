<?php

namespace LaravelGuard\Tests\Unit;

use LaravelGuard\Tenant\Contracts\TenantResolver;
use LaravelGuard\Tenant\TenantContext;
use PHPUnit\Framework\TestCase;

final class TenantContextTest extends TestCase
{
    public function test_null_resolution_can_retry_after_authentication_becomes_available(): void
    {
        $resolver = new class implements TenantResolver
        {
            public string|int|null $value = null;

            public function currentTenantId(): string|int|null
            {
                return $this->value;
            }
        };
        $context = new TenantContext($resolver);

        $this->assertNull($context->id());
        $resolver->value = 42;
        $this->assertSame(42, $context->id());
        $resolver->value = 99;
        $this->assertSame(42, $context->id(), 'A resolved request context must remain stable.');
    }

    public function test_resolution_is_reentrancy_safe(): void
    {
        $context = null;
        $resolver = new class($context) implements TenantResolver
        {
            public ?TenantContext $context = null;

            public function __construct(?TenantContext &$context)
            {
                $this->context = $context;
            }

            public function currentTenantId(): string|int|null
            {
                $this->context?->id();

                return 7;
            }
        };
        $context = new TenantContext($resolver);
        $resolver->context = $context;

        $this->assertSame(7, $context->id());
    }
}
