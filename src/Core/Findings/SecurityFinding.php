<?php

namespace LaravelGuard\Core\Findings;

use JsonSerializable;
use LaravelGuard\Core\Contracts\GuardRule;
use LaravelGuard\Core\Support\SourceLocation;

final readonly class SecurityFinding implements JsonSerializable
{
    public function __construct(
        public string $ruleId,
        public string $category,
        public Severity $severity,
        public Confidence $confidence,
        public string $title,
        public string $description,
        public string $risk,
        public string $recommendation,
        public SourceLocation $location = new SourceLocation,
        public array $metadata = [],
    ) {}

    public static function fromRule(
        GuardRule $rule,
        string $description,
        string $risk,
        string $recommendation,
        Confidence $confidence = Confidence::High,
        ?string $file = null,
        ?int $line = null,
        array $metadata = [],
    ): self {
        return new self($rule->id(), $rule->category(), $rule->severity(), $confidence, $rule->name(), $description, $risk, $recommendation, new SourceLocation($file, $line), $metadata);
    }

    public function fingerprint(): string
    {
        $identity = [$this->ruleId, $this->location->file, $this->location->line, $this->metadata['symbol'] ?? $this->description];

        return hash('sha256', json_encode($identity, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    public function jsonSerialize(): array
    {
        return [
            'fingerprint' => $this->fingerprint(), 'rule_id' => $this->ruleId, 'category' => $this->category,
            'severity' => strtolower($this->severity->name), 'confidence' => $this->confidence->value,
            'title' => $this->title, 'description' => $this->description, 'risk' => $this->risk,
            'recommendation' => $this->recommendation, ...$this->location->toArray(), 'metadata' => $this->metadata,
        ];
    }
}
