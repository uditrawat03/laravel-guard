<?php

namespace LaravelGuard\Tests\Unit;

use LaravelGuard\Core\Diff\GitDiff;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Reporting\GithubReporter;
use LaravelGuard\Core\Reporting\HtmlReporter;
use LaravelGuard\Core\Reporting\JunitReporter;
use LaravelGuard\Core\Reporting\SarifReporter;
use LaravelGuard\Core\Scoring\SecurityScore;
use LaravelGuard\Core\Support\SourceLocation;
use LaravelGuard\Tests\TestCase;

final class ReportingTest extends TestCase
{
    public function test_reporters_emit_supported_formats_and_score(): void
    {
        $findings = $this->findings();

        $this->assertStringContainsString('2.1.0', (new SarifReporter)->render($findings));
        $this->assertStringContainsString('::error', (new GithubReporter)->render($findings));
        $this->assertStringContainsString('<testsuite', (new JunitReporter)->render($findings));
        $this->assertStringContainsString('<!doctype html>', (new HtmlReporter)->render($findings));
        $this->assertSame(90, SecurityScore::fromFindings($findings)->score);
    }

    public function test_git_diff_filters_findings_to_added_lines(): void
    {
        $diff = new GitDiff("+++ b/app/Service.php\n@@ -2,0 +3,2 @@\n+unsafe\n+unsafe\n");
        $filtered = $diff->newFindings($this->findings());

        $this->assertCount(1, $filtered);
    }

    private function findings(): FindingCollection
    {
        return (new FindingCollection)->add(new SecurityFinding('LG-TEST-001', 'test', Severity::High, Confidence::High, 'Test', 'Description', 'Risk', 'Fix', new SourceLocation('C:/project/app/Service.php', 3), ['symbol' => 'App\\Service::run']));
    }
}
