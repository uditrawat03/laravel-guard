<?php

namespace LaravelGuard\Tests\Unit;

use LaravelGuard\Core\Baseline\BaselineDocument;
use LaravelGuard\Core\Diff\GitDiff;
use LaravelGuard\Core\Diff\SecurityDiff;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Reporting\JsonReporter;
use LaravelGuard\Core\Reporting\JunitReporter;
use LaravelGuard\Core\Reporting\SarifReporter;
use LaravelGuard\Core\Support\OutputSchema;
use LaravelGuard\Tests\TestCase;

final class OutputSchemaContractTest extends TestCase
{
    public function test_json_report_has_a_stable_schema_identity(): void
    {
        $report = json_decode((new JsonReporter)->render($this->findings()), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(OutputSchema::REPORT, $report['schema']);
        $this->assertSame(OutputSchema::REPORT_VERSION, $report['schema_version']);
        $this->assertSame(1, $report['total']);
        $this->assertArrayHasKey('findings', $report);
    }

    public function test_diff_and_baseline_have_independent_schema_versions(): void
    {
        $diff = SecurityDiff::compare($this->findings(), null, new GitDiff("+++ b/Test.php\n@@ -0,0 +1 @@\n+unsafe\n"))->jsonSerialize();
        $baseline = BaselineDocument::fromFindings($this->findings(), 'security', 'reviewed', null)->jsonSerialize();

        $this->assertSame(OutputSchema::DIFF, $diff['schema']);
        $this->assertSame(OutputSchema::DIFF_VERSION, $diff['schema_version']);
        $this->assertSame(OutputSchema::BASELINE, $baseline['schema']);
        $this->assertSame(OutputSchema::BASELINE_VERSION, $baseline['schema_version']);
    }

    public function test_sarif_and_junit_expose_the_laravel_guard_contract_version(): void
    {
        $sarif = json_decode((new SarifReporter)->render($this->findings()), true, flags: JSON_THROW_ON_ERROR);
        $junit = (new JunitReporter)->render($this->findings());

        $this->assertSame('2.1.0', $sarif['version']);
        $this->assertSame(OutputSchema::REPORT_VERSION, $sarif['runs'][0]['properties']['laravelGuardSchemaVersion']);
        $this->assertStringContainsString('name="laravel-guard.schema-version" value="1"', $junit);
    }

    public function test_packaged_json_schema_documents_are_valid_and_identified(): void
    {
        $expected = [
            'report-v1.json' => 'urn:laravel-guard:schema:report:1',
            'diff-v1.json' => 'urn:laravel-guard:schema:diff:1',
            'baseline-v3.json' => 'urn:laravel-guard:schema:baseline:3',
        ];

        foreach ($expected as $file => $id) {
            $schema = json_decode(file_get_contents(dirname(__DIR__, 2).'/resources/schemas/'.$file), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame('https://json-schema.org/draft/2020-12/schema', $schema['$schema']);
            $this->assertSame($id, $schema['$id']);
            $this->assertNotEmpty($schema['required']);
        }
    }

    private function findings(): FindingCollection
    {
        return (new FindingCollection)->add(new SecurityFinding(
            'LG-TEST-001', 'test', Severity::High, Confidence::High, 'Test finding',
            'Unsafe operation.', 'Security risk.', 'Use a safe operation.',
        ));
    }
}
