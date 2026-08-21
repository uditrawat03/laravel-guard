<?php

namespace LaravelGuard\Core\Diff;

use JsonSerializable;
use LaravelGuard\Core\Findings\FindingCollection;

final readonly class SecurityDiff implements JsonSerializable
{
    public function __construct(public FindingCollection $introduced, public array $resolved) {}

    public static function compare(FindingCollection $current, ?BaselineSnapshot $baseline, GitDiff $lines): self
    {
        if ($baseline === null) {
            return new self($lines->newFindings($current), []);
        }

        $introduced = $current->withoutFingerprints($baseline->fingerprints);
        $currentFingerprints = array_map(fn ($finding) => $finding->fingerprint(), $current->all());
        $resolved = array_values(array_filter($baseline->findings, function (array $finding) use ($currentFingerprints): bool {
            return isset($finding['fingerprint']) && ! in_array($finding['fingerprint'], $currentFingerprints, true);
        }));

        return new self($introduced, $resolved);
    }

    public function jsonSerialize(): array
    {
        return [
            'summary' => ['introduced' => $this->introduced->count(), 'resolved' => count($this->resolved)],
            'introduced' => $this->introduced,
            'resolved' => $this->resolved,
        ];
    }
}
