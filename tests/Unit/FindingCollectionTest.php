<?php

namespace LaravelGuard\Tests\Unit;

use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Tests\TestCase;

final class FindingCollectionTest extends TestCase
{
    public function test_it_filters_at_every_severity_boundary_without_mutating_the_source(): void
    {
        $critical = $this->finding('LG-X-001', Severity::Critical);
        $high = $this->finding('LG-X-002', Severity::High);
        $medium = $this->finding('LG-X-003', Severity::Medium);
        $low = $this->finding('LG-X-004', Severity::Low);
        $items = (new FindingCollection)->add($low)->add($critical)->add($medium)->add($high);

        $this->assertSame([$low, $critical, $medium, $high], $items->all());
        $this->assertSame([$critical], $items->atOrAbove(Severity::Critical)->all());
        $this->assertSame([$critical, $high], $items->atOrAbove(Severity::High)->all());
        $this->assertSame([$critical, $medium, $high], $items->atOrAbove(Severity::Medium)->all());
        $this->assertSame([$low, $critical, $medium, $high], $items->atOrAbove(Severity::Low)->all());
        $this->assertCount(4, $items);
    }

    public function test_it_counts_serializes_and_iterates_findings_in_insertion_order(): void
    {
        $critical = $this->finding('LG-X-001', Severity::Critical);
        $high = $this->finding('LG-X-002', Severity::High);
        $items = (new FindingCollection)->add($critical)->add($high)->add($high);

        $this->assertSame(['critical' => 1, 'high' => 2, 'medium' => 0, 'low' => 0], $items->counts());
        $this->assertSame([$critical, $high, $high], iterator_to_array($items));
        $this->assertSame([$critical, $high, $high], $items->jsonSerialize());
    }

    public function test_it_removes_only_matching_fingerprints_and_preserves_order(): void
    {
        $first = $this->finding('LG-X-001', Severity::High);
        $second = $this->finding('LG-X-002', Severity::High);
        $third = $this->finding('LG-X-003', Severity::High);
        $items = (new FindingCollection)->add($first)->add($second)->add($third);

        $filtered = $items->withoutFingerprints([$second->fingerprint()]);

        $this->assertSame([$first, $third], $filtered->all());
        $this->assertSame([$first, $second, $third], $items->all());
    }

    private function finding(string $ruleId, Severity $severity): SecurityFinding
    {
        return new SecurityFinding($ruleId, 'test', $severity, Confidence::High, 'Title', 'Description', 'Risk', 'Fix');
    }
}
