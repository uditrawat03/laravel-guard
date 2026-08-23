<?php

namespace LaravelGuard\Tests\Integration;

use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Integrations\Debugbar\DebugbarIntegration;
use LaravelGuard\Integrations\Events\SecurityScanCompleted;
use LaravelGuard\Integrations\SpatieMultitenancy\SpatieTenantResolver;
use LaravelGuard\Integrations\SpatiePermission\SpatiePermissionIntegration;
use LaravelGuard\Integrations\StanclTenancy\StanclTenantResolver;
use LaravelGuard\Integrations\Telescope\TelescopeIntegration;
use LaravelGuard\Tests\TestCase;
use Spatie\Multitenancy\Models\Tenant;

final class OptionalPackageIntegrationTest extends TestCase
{
    public function test_spatie_permission_exposes_supported_middleware_aliases(): void
    {
        $this->requireClass('Spatie\\Permission\\PermissionServiceProvider', 'spatie/laravel-permission');

        $integration = new SpatiePermissionIntegration;

        $this->assertTrue($integration->available());
        $this->assertSame(
            ['permission', 'role', 'role_or_permission'],
            $integration->middleware(),
        );
    }

    public function test_telescope_publishes_a_scan_event_through_laravel_events(): void
    {
        $this->requireClass('Laravel\\Telescope\\Telescope', 'laravel/telescope');
        $captured = null;
        $this->app['events']->listen(SecurityScanCompleted::class, function (SecurityScanCompleted $event) use (&$captured): void {
            $captured = $event;
        });

        $integration = new TelescopeIntegration($this->app['events']);
        $integration->publish(new FindingCollection);

        $this->assertTrue($integration->available());
        $this->assertInstanceOf(SecurityScanCompleted::class, $captured);
        $this->assertSame(0, $captured->total);
        $this->assertSame(100, $captured->score);
        $this->assertSame(['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0], $captured->counts);
    }

    public function test_debugbar_publishes_a_scan_summary_to_the_messages_collector(): void
    {
        $provider = $this->debugbarProvider();
        $this->app->register($provider);
        $debugbar = $this->app->make('debugbar');
        $integration = new DebugbarIntegration($this->app);

        $this->assertTrue($integration->available());
        $integration->publish(new FindingCollection);

        $collector = method_exists($debugbar, 'getMessagesCollector')
            ? $debugbar->getMessagesCollector()
            : $debugbar->getCollector('messages');
        $messages = json_encode($collector->collect(), JSON_THROW_ON_ERROR);

        $this->assertStringContainsString('laravel-guard', $messages);
        $this->assertStringContainsString('"score":100', $messages);
    }

    public function test_spatie_resolver_tracks_tenant_transitions_and_clears_between_jobs(): void
    {
        $this->requireClass('Spatie\\Multitenancy\\Models\\Tenant', 'spatie/laravel-multitenancy');
        $key = 'laravel-guard.current-tenant';
        $this->app['config']->set('multitenancy.current_tenant_container_key', $key);
        $resolver = new SpatieTenantResolver;

        $this->assertNull($resolver->currentTenantId());

        $first = new Tenant;
        $first->setAttribute($first->getKeyName(), 'tenant-a');
        $this->app->instance($key, $first);
        $this->assertSame('tenant-a', $resolver->currentTenantId());

        $second = new Tenant;
        $second->setAttribute($second->getKeyName(), 'tenant-b');
        $this->app->instance($key, $second);
        $this->assertSame('tenant-b', $resolver->currentTenantId());

        $this->app->offsetUnset($key);
        $this->assertNull($resolver->currentTenantId());
    }

    public function test_stancl_resolver_tracks_tenant_transitions_and_clears_between_jobs(): void
    {
        $contract = 'Stancl\\Tenancy\\Contracts\\Tenant';
        $this->requireClass($contract, 'stancl/tenancy');
        $resolver = new StanclTenantResolver;

        $this->assertNull($resolver->currentTenantId());

        $this->app->instance($contract, $this->stanclTenant('tenant-a'));
        $this->assertSame('tenant-a', $resolver->currentTenantId());

        $this->app->instance($contract, $this->stanclTenant('tenant-b'));
        $this->assertSame('tenant-b', $resolver->currentTenantId());

        $this->app->offsetUnset($contract);
        $this->assertNull($resolver->currentTenantId());
    }

    private function debugbarProvider(): string
    {
        foreach (['Fruitcake\\LaravelDebugbar\\ServiceProvider', 'Barryvdh\\Debugbar\\ServiceProvider'] as $provider) {
            if (class_exists($provider)) {
                return $provider;
            }
        }

        $this->markTestSkipped('barryvdh/laravel-debugbar is not installed.');
    }

    private function stanclTenant(string $id): \Stancl\Tenancy\Contracts\Tenant
    {
        return new class($id) implements \Stancl\Tenancy\Contracts\Tenant
        {
            public function __construct(private readonly string $id) {}

            public function getTenantKeyName(): string
            {
                return 'id';
            }

            public function getTenantKey(): string
            {
                return $this->id;
            }

            public function getInternal(string $key): mixed
            {
                return $key === 'id' ? $this->id : null;
            }

            public function setInternal(string $key, mixed $value): void {}

            public function run(callable $callback): mixed
            {
                return $callback($this);
            }
        };
    }

    private function requireClass(string $class, string $package): void
    {
        if (! class_exists($class)) {
            $this->markTestSkipped("{$package} is not installed.");
        }
    }
}
