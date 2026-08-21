<?php

namespace LaravelGuard\Tests\Unit;

use Illuminate\Routing\Route;
use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Routes\AuthorizationInspector;
use LaravelGuard\Tests\Fixtures\ResourceController;
use LaravelGuard\Tests\TestCase;

final class AuthorizationRouteTest extends TestCase
{
    public function test_authorize_resource_applies_to_controller_route(): void
    {
        $route = new Route(['DELETE'], 'documents/{document}', ['uses' => ResourceController::class.'@destroy']);

        $this->assertTrue($this->app->make(AuthorizationInspector::class)->controllerAuthorizes($route, $this->app->make(SecurityContext::class)));
    }
}
