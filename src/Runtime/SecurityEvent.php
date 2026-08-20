<?php

namespace LaravelGuard\Runtime;

final readonly class SecurityEvent
{
    public function __construct(
        public string $ruleId,
        public string $message,
        public array $metadata,
        public ?string $file,
        public ?int $line,
        public string $createdAt,
    ) {}
}
