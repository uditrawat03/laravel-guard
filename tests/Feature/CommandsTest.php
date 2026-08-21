<?php

namespace LaravelGuard\Tests\Feature;

use LaravelGuard\Tests\TestCase;

final class CommandsTest extends TestCase
{
    public function test_rules_command_lists_registered_rules(): void
    {
        $this->artisan('guard:rules')->expectsOutputToContain('LG-CONFIG-001')->assertSuccessful();
    }

    public function test_console_scan_uses_public_command_output_api(): void
    {
        $this->artisan('guard:scan', ['--module' => 'uploads'])
            ->expectsOutputToContain('Laravel Guard Security Scan')
            ->assertSuccessful();
    }

    public function test_check_fails_at_configured_threshold(): void
    {
        $this->artisan('guard:check', ['--module' => 'uploads', '--fail-on' => 'high', '--format' => 'json', '--no-baseline' => true])->assertFailed();
    }
}
