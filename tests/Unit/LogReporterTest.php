<?php

namespace LaravelGuard\Tests\Unit;

use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Reporting\LogReporter;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

final class LogReporterTest extends TestCase
{
    public function test_log_reporter_emits_metadata_without_finding_metadata_values(): void
    {
        $logger = new class extends AbstractLogger
        {
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = compact('level', 'message', 'context');
            }
        };
        $finding = new SecurityFinding('LG-SECRET-001', 'secrets', Severity::Critical, Confidence::High, 'Secret', 'Masked', 'Risk', 'Rotate', metadata: ['secret' => 'must-not-log']);
        $findings = new FindingCollection;
        $findings->add($finding);

        $this->assertSame('', (new LogReporter($logger))->render($findings));
        $this->assertCount(1, $logger->records);
        $this->assertStringNotContainsString('must-not-log', json_encode($logger->records, JSON_THROW_ON_ERROR));
    }
}
