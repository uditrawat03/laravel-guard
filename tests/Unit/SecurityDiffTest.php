<?php

namespace LaravelGuard\Tests\Unit;

use LaravelGuard\Core\Diff\BaselineSnapshot;
use LaravelGuard\Core\Diff\GitDiff;
use LaravelGuard\Core\Diff\SecurityDiff;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Support\SourceLocation;
use PHPUnit\Framework\TestCase;

final class SecurityDiffTest extends TestCase
{
    public function test_compares_introduced_and_resolved_fingerprints(): void
    {
        $currentFinding = new SecurityFinding('LG-NEW-001', 'test', Severity::High, Confidence::High, 'New risk', 'New description', 'Risk', 'Fix');
        $current = (new FindingCollection)->add($currentFinding);
        $baseline = new BaselineSnapshot(['old-fingerprint'], [[
            'fingerprint' => 'old-fingerprint', 'rule_id' => 'LG-OLD-001', 'severity' => 'medium', 'title' => 'Old risk',
        ]]);

        $diff = SecurityDiff::compare($current, $baseline, new GitDiff(''));

        $this->assertCount(1, $diff->introduced);
        $this->assertSame('LG-OLD-001', $diff->resolved[0]['rule_id']);
        $this->assertSame(['introduced' => 1, 'resolved' => 1], $diff->jsonSerialize()['summary']);
    }

    public function test_falls_back_to_changed_lines_without_a_historical_baseline(): void
    {
        $finding = new SecurityFinding('LG-NEW-001', 'test', Severity::High, Confidence::High, 'New risk', 'New description', 'Risk', 'Fix', new SourceLocation('C:/project/app/Service.php', 3));
        $current = (new FindingCollection)->add($finding);
        $lines = new GitDiff("+++ b/app/Service.php\n@@ -2,0 +3,1 @@\n+unsafe\n");

        $this->assertCount(1, SecurityDiff::compare($current, null, $lines)->introduced);
    }
}
