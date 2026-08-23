<?php

namespace LaravelGuard\Tests\Unit;

use DateTimeImmutable;
use LaravelGuard\Core\Baseline\BaselineDocument;
use LaravelGuard\Core\Baseline\BaselineEntry;
use PHPUnit\Framework\TestCase;

final class BaselineDocumentTest extends TestCase
{
    public function test_it_reads_legacy_baselines_and_preserves_compatibility_fields(): void
    {
        $document = BaselineDocument::fromJson(json_encode([
            'schema_version' => 2,
            'generated_at' => '2026-01-01T00:00:00+00:00',
            'fingerprints' => ['legacy-fingerprint'],
            'findings' => [['fingerprint' => 'legacy-fingerprint', 'rule_id' => 'LG-TEST-001', 'severity' => 'high', 'title' => 'Legacy']],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame(2, $document->sourceSchema);
        $this->assertSame(['legacy-fingerprint'], $document->activeFingerprints());
        $encoded = $document->jsonSerialize();
        $this->assertSame(3, $encoded['schema_version']);
        $this->assertSame(['legacy-fingerprint'], $encoded['fingerprints']);
    }

    public function test_expired_entries_are_not_active_and_pruning_removes_expired_and_resolved_entries(): void
    {
        $now = new DateTimeImmutable('2026-08-23T00:00:00+00:00');
        $document = new BaselineDocument([
            new BaselineEntry('active', 'LG-TEST-001', 'high', 'Active', expiresAt: '2026-09-01T00:00:00+00:00'),
            new BaselineEntry('expired', 'LG-TEST-002', 'high', 'Expired', expiresAt: '2026-08-01T00:00:00+00:00'),
            new BaselineEntry('resolved', 'LG-TEST-003', 'low', 'Resolved'),
        ], '2026-08-01T00:00:00+00:00');

        $this->assertSame(['active', 'resolved'], $document->activeFingerprints($now));
        $this->assertSame(['expired'], array_map(fn ($entry) => $entry->fingerprint, $document->expired($now)));
        $this->assertSame(['active'], array_map(fn ($entry) => $entry->fingerprint, $document->pruned(['active', 'expired'], $now)->entries));
    }
}
