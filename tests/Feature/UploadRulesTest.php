<?php

namespace LaravelGuard\Tests\Feature;

use LaravelGuard\LaravelGuard;
use LaravelGuard\Tests\TestCase;

final class UploadRulesTest extends TestCase
{
    public function test_unsafe_upload_patterns_include_source_locations(): void
    {
        $findings = $this->app->make(LaravelGuard::class)->scan('uploads');
        $ids = array_map(fn ($f) => $f->ruleId, $findings->all());
        $this->assertContains('LG-UPLOAD-001', $ids);
        $this->assertContains('LG-UPLOAD-002', $ids);
        $this->assertNotNull($findings->all()[0]->location->line);
    }
}
