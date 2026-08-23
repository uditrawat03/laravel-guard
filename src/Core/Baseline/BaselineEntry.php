<?php

namespace LaravelGuard\Core\Baseline;

use DateTimeImmutable;
use JsonSerializable;
use LaravelGuard\Core\Findings\SecurityFinding;

final readonly class BaselineEntry implements JsonSerializable
{
    public function __construct(
        public string $fingerprint,
        public string $ruleId,
        public string $severity,
        public string $title,
        public ?string $file = null,
        public ?string $owner = null,
        public ?string $reason = null,
        public ?string $createdAt = null,
        public ?string $expiresAt = null,
    ) {}

    public static function fromFinding(SecurityFinding $finding, ?string $owner, ?string $reason, string $createdAt, ?string $expiresAt): self
    {
        return new self(
            $finding->fingerprint(),
            $finding->ruleId,
            strtolower($finding->severity->name),
            $finding->title,
            $finding->location->file,
            $owner,
            $reason,
            $createdAt,
            $expiresAt,
        );
    }

    public static function fromArray(array $data): ?self
    {
        $fingerprint = $data['fingerprint'] ?? null;
        if (! is_string($fingerprint) || $fingerprint === '') {
            return null;
        }

        $acceptance = is_array($data['acceptance'] ?? null) ? $data['acceptance'] : [];

        return new self(
            $fingerprint,
            self::string($data['rule_id'] ?? null) ?? 'unknown',
            self::string($data['severity'] ?? null) ?? 'unknown',
            self::string($data['title'] ?? null) ?? 'Unknown finding',
            self::string($data['file'] ?? null),
            self::string($acceptance['owner'] ?? $data['owner'] ?? null),
            self::string($acceptance['reason'] ?? $data['reason'] ?? null),
            self::string($acceptance['created_at'] ?? $data['created_at'] ?? null),
            self::string($acceptance['expires_at'] ?? $data['expires_at'] ?? null),
        );
    }

    public function isExpired(?DateTimeImmutable $now = null): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        try {
            return new DateTimeImmutable($this->expiresAt) <= ($now ?? new DateTimeImmutable);
        } catch (\Exception) {
            return true;
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'fingerprint' => $this->fingerprint,
            'rule_id' => $this->ruleId,
            'severity' => $this->severity,
            'title' => $this->title,
            'file' => $this->file,
            'acceptance' => [
                'owner' => $this->owner,
                'reason' => $this->reason,
                'created_at' => $this->createdAt,
                'expires_at' => $this->expiresAt,
            ],
        ];
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
