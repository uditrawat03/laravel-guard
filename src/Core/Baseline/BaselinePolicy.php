<?php

namespace LaravelGuard\Core\Baseline;

use DateTimeImmutable;

final readonly class BaselinePolicy
{
    /** @param list<string> $allowedSeverities */
    public function __construct(
        public bool $requireReason = true,
        public bool $requireExpiration = true,
        public int $maxEntries = 500,
        public int $maxTtlDays = 90,
        public int $requiredApprovals = 1,
        public array $allowedSeverities = ['low', 'medium', 'high', 'critical'],
    ) {}

    public static function fromConfig(array $config): self
    {
        foreach (['require_reason', 'require_expiration'] as $key) {
            if (array_key_exists($key, $config) && ! is_bool($config[$key])) {
                throw new \InvalidArgumentException("{$key} must be a boolean.");
            }
        }
        foreach (['max_entries', 'max_ttl_days', 'required_approvals'] as $key) {
            if (array_key_exists($key, $config) && ! is_int($config[$key])) {
                throw new \InvalidArgumentException("{$key} must be an integer.");
            }
        }
        if (array_key_exists('allowed_severities', $config) && ! is_array($config['allowed_severities'])) {
            throw new \InvalidArgumentException('allowed_severities must be an array.');
        }

        return new self(
            requireReason: $config['require_reason'] ?? true,
            requireExpiration: $config['require_expiration'] ?? true,
            maxEntries: $config['max_entries'] ?? 500,
            maxTtlDays: $config['max_ttl_days'] ?? 90,
            requiredApprovals: $config['required_approvals'] ?? 1,
            allowedSeverities: $config['allowed_severities'] ?? ['low', 'medium', 'high', 'critical'],
        );
    }

    /** @return list<string> */
    public function configurationErrors(): array
    {
        $errors = [];
        if ($this->maxEntries < 0) {
            $errors[] = 'max_entries must be a non-negative integer.';
        }
        if ($this->maxTtlDays < 0) {
            $errors[] = 'max_ttl_days must be a non-negative integer.';
        }
        if ($this->requiredApprovals < 1) {
            $errors[] = 'required_approvals must be at least 1.';
        }
        $invalid = array_diff($this->allowedSeverities, ['low', 'medium', 'high', 'critical']);
        if ($this->allowedSeverities === [] || $invalid !== []) {
            $errors[] = 'allowed_severities must contain only low, medium, high, or critical.';
        }

        return $errors;
    }

    /** @return list<string> */
    public function violations(BaselineDocument $document, ?DateTimeImmutable $now = null): array
    {
        $violations = $this->configurationErrors();
        if (count($document->entries) > $this->maxEntries) {
            $violations[] = 'Baseline contains '.count($document->entries)." entries; policy allows {$this->maxEntries}.";
        }

        foreach ($document->entries as $entry) {
            $label = "Baseline entry [{$entry->ruleId}/".substr($entry->fingerprint, 0, 12).']';
            if (! in_array($entry->severity, $this->allowedSeverities, true)) {
                $violations[] = "{$label} has disallowed severity [{$entry->severity}].";
            }
            if ($this->requireReason && $entry->reason === null) {
                $violations[] = "{$label} has no acceptance reason.";
            }
            $approvers = array_unique(array_filter([$entry->owner, ...$entry->approvers], fn ($value) => is_string($value) && trim($value) !== ''));
            if (count($approvers) < $this->requiredApprovals) {
                $violations[] = "{$label} has ".count($approvers)." approval(s); policy requires {$this->requiredApprovals}.";
            }
            if ($this->requireExpiration && $entry->expiresAt === null) {
                $violations[] = "{$label} has no expiration date.";
            }
            if ($entry->createdAt !== null && $entry->expiresAt !== null && $this->maxTtlDays > 0) {
                try {
                    $created = new DateTimeImmutable($entry->createdAt);
                    $expires = new DateTimeImmutable($entry->expiresAt);
                    if ($expires->getTimestamp() - $created->getTimestamp() > $this->maxTtlDays * 86400) {
                        $violations[] = "{$label} exceeds the {$this->maxTtlDays}-day maximum TTL.";
                    }
                } catch (\Exception) {
                    $violations[] = "{$label} has an invalid governance date.";
                }
            }
        }

        return array_values(array_unique($violations));
    }
}
