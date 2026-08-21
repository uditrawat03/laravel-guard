<?php

namespace LaravelGuard\Tests\Unit;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Source\SourceIndex;
use LaravelGuard\Tests\TestCase;

final class AuthorizationInspectorTest extends TestCase
{
    public function test_source_index_attributes_authorize_resource_to_controller_constructor(): void
    {
        $context = $this->app->make(SecurityContext::class);
        $calls = iterator_to_array($this->app->make(SourceIndex::class)->calls($context, ['authorizeResource']));
        $symbols = array_map(fn ($call) => $call->symbol, $calls);

        $this->assertContains('LaravelGuard\\Tests\\Fixtures\\ResourceController::__construct', $symbols, json_encode($symbols));
    }
}
