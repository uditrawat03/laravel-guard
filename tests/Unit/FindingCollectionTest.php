<?php

namespace LaravelGuard\Tests\Unit;

use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Tests\TestCase;

final class FindingCollectionTest extends TestCase
{
    public function test_it_filters_by_severity_and_produces_stable_fingerprints(): void
    {
        $finding = new SecurityFinding('LG-X-001', 'test', Severity::High, Confidence::High, 'Title', 'Description', 'Risk', 'Fix');
        $items = (new FindingCollection)->add($finding);
        $this->assertCount(1, $items->atOrAbove(Severity::High));
        $this->assertCount(0, $items->atOrAbove(Severity::Critical));
        $this->assertSame($finding->fingerprint(), $finding->fingerprint());
    }
}
