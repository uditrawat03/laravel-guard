<?php

namespace LaravelGuard\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use LaravelGuard\Tests\TestCase;

final class UiDashboardTest extends TestCase
{
    private string $scanDirectory;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $this->scanDirectory = storage_path('framework/testing/laravel-guard-ui-'.uniqid());
        $app['config']->set('cache.default', 'array');
        $app['config']->set('laravel-guard.ui', [
            'enabled' => true,
            'path' => '_guard',
            'middleware' => [],
            'ability' => 'viewLaravelGuard',
            'allow_scan' => true,
            'scan_on_first_view' => false,
            'storage_path' => $this->scanDirectory,
            'retention_days' => 30,
            'per_page' => 10,
            'read_rate_limit' => 120,
            'scan_rate_limit' => 3,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Gate::define('viewLaravelGuard', fn ($user = null) => true);
    }

    protected function tearDown(): void
    {
        if (isset($this->scanDirectory) && is_dir($this->scanDirectory)) {
            app('files')->deleteDirectory($this->scanDirectory);
        }
        parent::tearDown();
    }

    public function test_authorized_user_can_open_package_dashboard_and_asset(): void
    {
        $this->get('/_guard')->assertOk()->assertSee('Laravel Guard')->assertSee('No scan evidence yet');
        $this->get('/_guard/assets/app.css')->assertOk()->assertHeader('Content-Type', 'text/css; charset=UTF-8');
        $this->assertContains('throttle:laravel-guard-ui', app('router')->getRoutes()->getByName('laravel-guard.ui.overview')->gatherMiddleware());
        $this->assertContains('throttle:laravel-guard-ui-scan', app('router')->getRoutes()->getByName('laravel-guard.ui.scan')->gatherMiddleware());
    }

    public function test_browsing_dashboard_does_not_consume_scan_rate_limit(): void
    {
        foreach (range(1, 4) as $visit) {
            $this->get('/_guard/rules?page='.$visit)->assertOk();
        }

        $this->post('/_guard/scans')->assertRedirect('/_guard/overview');
    }

    public function test_routes_remain_registered_when_dashboard_is_disabled(): void
    {
        config()->set('laravel-guard.ui.enabled', false);

        $this->assertNotNull(app('router')->getRoutes()->getByName('laravel-guard.ui.overview'));
        $this->get('/_guard')->assertNotFound();
    }

    public function test_rule_catalog_uses_compact_package_pagination(): void
    {
        $this->get('/_guard/rules')
            ->assertOk()
            ->assertSee('class="guard-pagination"', false)
            ->assertSee('active rules')
            ->assertDontSee('<svg', false);
    }

    public function test_doctor_uses_compact_diagnostic_layout(): void
    {
        $this->get('/_guard/doctor')
            ->assertOk()
            ->assertSee('guard-doctor-summary', false)
            ->assertSee('guard-diagnostic-status pass', false)
            ->assertSee('configuration checks');
    }

    public function test_dashboard_fails_closed_when_ability_is_missing(): void
    {
        config()->set('laravel-guard.ui.ability', 'undefinedLaravelGuardAbility');

        $this->get('/_guard')->assertForbidden();
    }

    public function test_web_scan_persists_redacted_package_owned_evidence(): void
    {
        $this->post('/_guard/scans')->assertRedirect('/_guard/overview');
        $this->get('/_guard/findings')->assertOk()->assertSee('Findings');

        $files = glob($this->scanDirectory.'/*.json');
        $this->assertCount(1, $files);
        $report = json_decode((string) file_get_contents($files[0]), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('laravel-guard/ui-scan', $report['schema']);
        $this->assertArrayNotHasKey('metadata', $report['findings'][0]);
        $this->assertStringStartsWith('https://', $report['findings'][0]['documentation_url']);
        $this->get('/_guard/findings')->assertOk()->assertSee('Rule guidance');
        $this->assertStringNotContainsString(str_replace('\\', '/', base_path()), (string) file_get_contents($files[0]));
    }

    public function test_scan_action_can_be_disabled_independently(): void
    {
        config()->set('laravel-guard.ui.allow_scan', false);

        $this->post('/_guard/scans')->assertForbidden();
    }
}
