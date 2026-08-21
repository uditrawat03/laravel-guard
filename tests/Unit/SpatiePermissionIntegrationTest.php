<?php

namespace LaravelGuard\Tests\Unit;

use LaravelGuard\Integrations\SpatiePermission\SpatiePermissionIntegration;
use PHPUnit\Framework\TestCase;

final class SpatiePermissionIntegrationTest extends TestCase
{
    public function test_exposes_all_supported_authorization_middleware(): void
    {
        $integration = new SpatiePermissionIntegration;

        $this->assertSame(
            ['permission', 'role', 'role_or_permission'],
            $integration->authorizationMiddleware(),
        );
    }
}
