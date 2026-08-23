<?php

namespace LaravelGuard\Core\Baseline;

use DateTimeImmutable;
use JsonSerializable;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Support\OutputSchema;

final readonly class BaselineDocument implements JsonSerializable
{
    public const SCHEMA_VERSION = OutputSchema::BASELINE_VERSION;

    /** @param list<BaselineEntry> $entries */
    public function __construct(public array $entries, public string $generatedAt, public int $sourceSchema = self::SCHEMA_VERSION) {}

    public static function fromFindings(FindingCollection $findings, ?string $owner, ?string $reason, ?string $expiresAt, ?string $createdAt = null): self
    {
        $createdAt ??= (new DateTimeImmutable)->format(DATE_ATOM);

        return new self(array_map(
            fn ($finding) => BaselineEntry::fromFinding($finding, $owner, $reason, $createdAt, $expiresAt),
            $findings->all(),
        ), $createdAt);
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($data)) {
            throw new \UnexpectedValueException('Baseline must contain a JSON object.');
        }

        $entries = [];
        foreach (is_array($data['findings'] ?? null) ? $data['findings'] : [] as $finding) {
            if (is_array($finding) && ($entry = BaselineEntry::fromArray($finding)) !== null) {
                $entries[$entry->fingerprint] = $entry;
            }
        }
        foreach (is_array($data['fingerprints'] ?? null) ? $data['fingerprints'] : [] as $fingerprint) {
            if (is_string($fingerprint) && $fingerprint !== '' && ! isset($entries[$fingerprint])) {
                $entries[$fingerprint] = new BaselineEntry($fingerprint, 'unknown', 'unknown', 'Legacy baseline finding');
            }
        }

        return new self(
            array_values($entries),
            is_string($data['generated_at'] ?? null) ? $data['generated_at'] : (new DateTimeImmutable)->format(DATE_ATOM),
            is_int($data['schema_version'] ?? null) ? $data['schema_version'] : 1,
        );
    }

    /** @return list<string> */
    public function activeFingerprints(?DateTimeImmutable $now = null): array
    {
        return array_values(array_map(
            fn (BaselineEntry $entry) => $entry->fingerprint,
            array_filter($this->entries, fn (BaselineEntry $entry) => ! $entry->isExpired($now)),
        ));
    }

    /** @return list<BaselineEntry> */
    public function expired(?DateTimeImmutable $now = null): array
    {
        return array_values(array_filter($this->entries, fn (BaselineEntry $entry) => $entry->isExpired($now)));
    }

    /** @return list<BaselineEntry> */
    public function matching(string $query): array
    {
        return array_values(array_filter($this->entries, fn (BaselineEntry $entry) => $entry->fingerprint === $query || $entry->ruleId === $query));
    }

    /** @param list<string> $currentFingerprints */
    public function pruned(array $currentFingerprints, ?DateTimeImmutable $now = null): self
    {
        return new self(array_values(array_filter(
            $this->entries,
            fn (BaselineEntry $entry) => ! $entry->isExpired($now) && in_array($entry->fingerprint, $currentFingerprints, true),
        )), (new DateTimeImmutable)->format(DATE_ATOM));
    }

    public function jsonSerialize(): array
    {
        return [
            'schema' => OutputSchema::BASELINE,
            'schema_version' => self::SCHEMA_VERSION,
            'generated_at' => $this->generatedAt,
            'fingerprints' => array_map(fn (BaselineEntry $entry) => $entry->fingerprint, $this->entries),
            'findings' => $this->entries,
        ];
    }
}
